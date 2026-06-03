<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Server;
use App\Models\SupportTicket;
use App\Models\Tariff;
use App\Models\User;
use App\Services\PterodactylService;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    /** GET /api/admin/dashboard */
    public function dashboard()
    {
        return response()->json([
            'total_users'      => User::count(),
            'active_servers'   => Server::where('status', 'active')->count(),
            'pending_servers'  => Server::whereIn('status', ['pending', 'provisioning'])->count(),
            'error_servers'    => Server::where('status', 'error')->count(),
            'total_revenue'    => Order::where('status', 'completed')->sum('amount'),
            'open_tickets'     => SupportTicket::where('status', 'open')->count(),
        ]);
    }

    /** GET /api/admin/users */
    public function users(Request $request)
    {
        $q = User::query();
        if ($s = $request->query('search')) {
            $q->where('email', 'like', "%{$s}%");
        }
        return response()->json($q->withCount('servers')->orderByDesc('id')->paginate(20));
    }

    /**
     * PUT /api/admin/users/{id}
     *
     * Админ может менять только баланс (прибавлять/убавлять).
     * Поле balance_delta: положительное — начислить, отрицательное — списать.
     * Для совместимости также принимает поле balance (прямая установка).
     */
    public function updateUser(Request $request, $id)
    {
        $request->validate([
            'balance'       => 'nullable|numeric',
            'balance_delta' => 'nullable|numeric',
        ]);

        $user = User::findOrFail($id);
        $oldBalance = (float) $user->balance;

        if ($request->has('balance_delta')) {
            $delta = (float) $request->balance_delta;
            $newBalance = round($oldBalance + $delta, 2);
            if ($newBalance < 0) {
                return response()->json(['error' => 'Баланс не может быть отрицательным'], 422);
            }
            $user->balance = $newBalance;
        } elseif ($request->has('balance')) {
            $newBalance = round((float) $request->balance, 2);
            if ($newBalance < 0) {
                return response()->json(['error' => 'Баланс не может быть отрицательным'], 422);
            }
            $user->balance = $newBalance;
        }

        $user->save();

        AuditLog::record('user.balance_changed', 'user', $user->id, [
            'old_balance' => $oldBalance,
            'new_balance' => (float) $user->balance,
            'delta'       => round((float) $user->balance - $oldBalance, 2),
        ]);

        return response()->json($user);
    }

    /**
     * PUT /api/admin/servers/{id}/tariff
     *
     * Смена тарифа сервера на любой доступный.
     */
    public function changeTariff(Request $request, $id)
    {
        $request->validate(['tariff_id' => 'required|exists:tariffs,id']);

        $server = Server::with('tariff')->findOrFail($id);
        $oldTariff = $server->tariff;
        $newTariff = Tariff::findOrFail($request->tariff_id);

        $server->update(['tariff_id' => $newTariff->id]);

        AuditLog::record('server.tariff_changed', 'server', $server->id, [
            'old_tariff' => $oldTariff ? ['id' => $oldTariff->id, 'name' => $oldTariff->name] : null,
            'new_tariff' => ['id' => $newTariff->id, 'name' => $newTariff->name],
            'user_id'    => $server->user_id,
        ]);

        return response()->json([
            'message' => "Тариф сервера #{$server->id} изменён на {$newTariff->name}",
            'server'  => $server->fresh()->load(['tariff', 'user:id,email']),
        ]);
    }

    /** GET /api/admin/audit */
    public function auditLog(Request $request)
    {
        $q = AuditLog::query()->with('admin:id,email');
        if ($action = $request->query('action')) {
            $q->where('action', $action);
        }
        if ($targetType = $request->query('target_type')) {
            $q->where('target_type', $targetType);
        }
        return response()->json($q->orderByDesc('created_at')->paginate(50));
    }

    /** GET /api/admin/servers — все сервера всех юзеров */
    public function servers(Request $request)
    {
        $q = Server::query()->with(['user:id,email', 'tariff:id,name', 'node:id,name']);
        if ($status = $request->query('status')) {
            $q->where('status', $status);
        }
        return response()->json($q->orderByDesc('id')->paginate(30));
    }

    /** POST /api/admin/servers/{id}/suspend */
    public function suspendServer($id, PterodactylService $ptero)
    {
        $server = Server::findOrFail($id);
        if ($server->ptero_server_id) {
            try { $ptero->suspendServer($server->ptero_server_id); }
            catch (\Throwable $e) { /* лог, продолжаем */ }
        }
        $server->update(['status' => 'suspended']);
        AuditLog::record('server.suspended', 'server', $server->id);
        return response()->json(['message' => 'Сервер приостановлен']);
    }

    /** POST /api/admin/servers/{id}/unsuspend */
    public function unsuspendServer($id, PterodactylService $ptero)
    {
        $server = Server::findOrFail($id);
        if ($server->ptero_server_id) {
            try { $ptero->unsuspendServer($server->ptero_server_id); }
            catch (\Throwable $e) {}
        }
        $server->update(['status' => 'active']);
        AuditLog::record('server.unsuspended', 'server', $server->id);
        return response()->json(['message' => 'Сервер разблокирован']);
    }

    /** POST /api/admin/users/{id}/verify-email */
    public function verifyUserEmail($id)
    {
        $user = User::findOrFail($id);

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email уже подтверждён.']);
        }

        $user->update(['email_verified_at' => now()]);

        Notification::create([
            'user_id' => $user->id,
            'title'   => 'Email подтверждён ✅',
            'message' => 'Ваш email был подтверждён администратором.',
            'type'    => 'success',
        ]);

        AuditLog::record('user.email_verified', 'user', $user->id, [
            'admin_id' => auth()->id(),
        ]);

        return response()->json(['message' => 'Email пользователя подтверждён']);
    }

    /** DELETE /api/admin/servers/{id} */
    public function deleteServer($id, PterodactylService $ptero)
    {
        $server = Server::findOrFail($id);

        // Удаляем сервер в Pterodactyl
        if ($server->ptero_server_id) {
            try { $ptero->deleteServer($server->ptero_server_id); }
            catch (\Throwable $e) {
                \Log::warning("Failed to delete ptero server {$server->ptero_server_id}: " . $e->getMessage());
            }
        }

        $server->update([
            'status'          => 'deleted',
            'ptero_server_id' => null,
            'server_ip'       => null,
            'server_port'     => null,
        ]);

        AuditLog::record('server.deleted', 'server', $server->id, [
            'user_id' => $server->user_id,
            'tariff'  => $server->tariff?->name,
        ]);

        // Уведомление пользователю
        Notification::create([
            'user_id' => $server->user_id,
            'title'   => 'Сервер удалён',
            'message' => "Ваш сервер #{$server->id} ({$server->tariff?->name}) был удалён администратором.",
            'type'    => 'warning',
        ]);

        return response()->json(['message' => 'Сервер удалён, порт освобождён']);
    }
}
