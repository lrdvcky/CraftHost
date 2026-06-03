<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\McVersion;
use App\Models\Order;
use App\Models\Server;
use App\Models\ServerMod;
use App\Services\BillingService;
use App\Services\PterodactylService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServerController extends Controller
{
    public function index(Request $request)
    {
        $servers = $request->user()
            ->servers()
            ->with(['tariff', 'node'])
            ->get()
            ->each->append('address');

        return response()->json($servers);
    }

    public function show(Request $request, $id)
    {
        $server = $request->user()
            ->servers()
            ->with(['tariff', 'node'])
            ->findOrFail($id);

        $server->append('address');
        return response()->json($server);
    }

    public function power(Request $request, $id, PterodactylService $ptero)
    {
        $request->validate(['signal' => 'required|in:start,stop,restart,kill']);

        $server = $request->user()->servers()->findOrFail($id);

        return match ($server->status) {
            'pending', 'provisioning' => response()->json(['error' => 'Сервер ещё создаётся, подождите 1-2 минуты.'], 409),
            'error'                   => response()->json(['error' => 'Сервер не удалось создать. Обратитесь в поддержку.'], 422),
            'suspended', 'deleted'    => response()->json(['error' => 'Сервер заблокирован или удалён.'], 403),
            'active'                  => $this->dispatchPowerSignal($server, $request->signal, $ptero),
            default                   => response()->json(['error' => "Неизвестный статус сервера: {$server->status}"], 500),
        };
    }

    /**
     * Смена ядра / версии Minecraft сервера.
     * POST /api/servers/{id}/change-core { mc_version }
     */
    public function changeCore(Request $request, $id, PterodactylService $ptero)
    {
        $request->validate(['mc_version' => 'required|string|max:50']);

        $server = $request->user()->servers()->findOrFail($id);

        if ($server->status !== 'active') {
            return response()->json(['error' => 'Сменить ядро можно только на активном сервере.'], 409);
        }
        if (!$server->ptero_server_id) {
            return response()->json(['error' => 'У сервера не привязан Pterodactyl ID.'], 422);
        }

        $newVersion = (string) $request->mc_version;
        if ($newVersion === $server->mc_version) {
            return response()->json(['error' => 'Это уже текущее ядро.'], 422);
        }

        $version = McVersion::where('slug', $newVersion)->where('is_active', true)->first();
        if (!$version) {
            return response()->json(['error' => 'Эта версия недоступна.'], 422);
        }

        try {
            $ptero->changeCoreAndReinstall($server, $newVersion);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Pterodactyl: ' . $e->getMessage()], 502);
        }

        // Чистим установленные моды — они привязаны к старому лоадеру.
        ServerMod::where('server_id', $server->id)->delete();

        $server->update(['mc_version' => $newVersion]);

        return response()->json([
            'message'    => "Ядро меняется на {$version->label}. Сервер переустанавливается, обычно 1-3 минуты.",
            'mc_version' => $newVersion,
        ]);
    }

    /**
     * Чтение и переключение режима «нелицензионные игроки».
     * GET  /api/servers/{id}/cracked   — { enabled: bool }
     * POST /api/servers/{id}/cracked   { enabled: bool }
     */
    public function getCracked(Request $request, $id, PterodactylService $ptero)
    {
        $server = $request->user()->servers()->findOrFail($id);
        if (!$server->ptero_server_id) {
            return response()->json(['enabled' => false]);
        }

        $props = $ptero->readFile($server->ptero_server_id, '/server.properties');
        $enabled = false;
        if ($props !== null && preg_match('/^online-mode\s*=\s*(true|false)/mi', $props, $m)) {
            $enabled = strtolower($m[1]) === 'false';
        }
        return response()->json(['enabled' => $enabled]);
    }

    public function setCracked(Request $request, $id, PterodactylService $ptero)
    {
        $request->validate(['enabled' => 'required|boolean']);
        $server = $request->user()->servers()->findOrFail($id);

        if ($server->status !== 'active' || !$server->ptero_server_id) {
            return response()->json(['error' => 'Сервер должен быть активным.'], 409);
        }

        $props = $ptero->readFile($server->ptero_server_id, '/server.properties') ?? '';
        if ($props === '') {
            return response()->json(['error' => 'Не удалось прочитать server.properties. Запусти сервер хотя бы раз.'], 422);
        }

        $value = $request->boolean('enabled') ? 'false' : 'true';

        if (preg_match('/^online-mode\s*=/mi', $props)) {
            $props = preg_replace('/^online-mode\s*=.*/mi', "online-mode={$value}", $props);
        } else {
            $props = rtrim($props) . "\nonline-mode={$value}\n";
        }

        try {
            $ptero->uploadFile($server->ptero_server_id, '/server.properties', $props);
            $ptero->sendPowerSignal($server->ptero_server_id, 'restart');
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Pterodactyl: ' . $e->getMessage()], 502);
        }

        return response()->json([
            'enabled' => $request->boolean('enabled'),
            'message' => $request->boolean('enabled')
                ? 'Нелицензионные игроки разрешены. Сервер перезапускается.'
                : 'Доступ только для лицензионных. Сервер перезапускается.',
        ]);
    }

    /**
     * Пересоздание карты мира.
     * POST /api/servers/{id}/regen-map
     */
    public function regenMap(Request $request, $id, PterodactylService $ptero)
    {
        $server = $request->user()->servers()->findOrFail($id);

        if ($server->status !== 'active') {
            return response()->json(['error' => 'Пересоздать мир можно только на активном сервере.'], 409);
        }
        if (!$server->ptero_server_id) {
            return response()->json(['error' => 'У сервера не привязан Pterodactyl ID.'], 422);
        }

        try {
            $ptero->regenMap($server->ptero_server_id);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Pterodactyl: ' . $e->getMessage()], 502);
        }

        return response()->json(['message' => 'Карта пересоздаётся. Сервер будет перезагружен.']);
    }

    private function dispatchPowerSignal(Server $server, string $signal, PterodactylService $ptero)
    {
        if (!$server->ptero_server_id) {
            return response()->json(['error' => 'У сервера не привязан Pterodactyl ID — переcоздайте заказ.'], 422);
        }

        // Проверяем текущее состояние, чтобы не слать конфликтные сигналы
        try {
            $stats = $ptero->getServerStats($server->ptero_server_id);
            $currentState = $stats['current_state'] ?? 'unknown';

            if (in_array($currentState, ['starting', 'stopping'])) {
                return response()->json([
                    'error' => 'Сервер сейчас ' . ($currentState === 'starting' ? 'запускается' : 'останавливается') . '. Подождите завершения операции.',
                    'state' => $currentState,
                ], 409);
            }

            // Не отправлять start если уже running
            if ($signal === 'start' && $currentState === 'running') {
                return response()->json(['message' => 'Сервер уже запущен', 'state' => 'running']);
            }
            // Не отправлять stop если уже stopped/offline
            if ($signal === 'stop' && in_array($currentState, ['stopped', 'offline'])) {
                return response()->json(['message' => 'Сервер уже остановлен', 'state' => 'stopped']);
            }
        } catch (\Throwable $e) {
            // Не блокируем, если не удалось получить статус — пробуем отправить сигнал
        }

        // Перед запуском/перезапуском гарантированно принимаем EULA: на этом
        // этапе сервер уже установлен, поэтому запись eula.txt всегда проходит.
        // Это устраняет ошибку "You need to agree to the EULA" для всех серверов.
        if (in_array($signal, ['start', 'restart'], true)) {
            try {
                $ptero->acceptEulaNow($server->ptero_server_id);
            } catch (\Throwable $e) {
                \Log::warning("EULA pre-start write failed for {$server->ptero_server_id}: " . $e->getMessage());
            }
        }

        try {
            $ptero->sendPowerSignal($server->ptero_server_id, $signal);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            // Обрабатываем 409 от Pterodactyl красиво
            if (str_contains($msg, '409') || str_contains($msg, 'ConflictException')) {
                return response()->json([
                    'error' => 'Сервер занят выполнением другой операции. Подождите несколько секунд и попробуйте снова.',
                ], 409);
            }
            return response()->json(['error' => 'Pterodactyl: ' . $msg], 502);
        }
        return response()->json(['message' => 'Сигнал питания отправлен']);
    }

    /**
     * Отправка консольной команды на сервер.
     * POST /api/servers/{id}/command
     */
    public function command(Request $request, $id, PterodactylService $ptero)
    {
        $request->validate(['command' => 'required|string|max:512']);

        $server = $request->user()->servers()->findOrFail($id);

        if ($server->status !== 'active') {
            return response()->json(['error' => 'Команды можно отправлять только активному серверу.'], 409);
        }
        if (!$server->ptero_server_id) {
            return response()->json(['error' => 'У сервера не привязан Pterodactyl ID.'], 422);
        }

        try {
            $ptero->sendCommand($server->ptero_server_id, $request->command);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Pterodactyl: ' . $e->getMessage()], 502);
        }

        return response()->json(['message' => 'Команда отправлена']);
    }

    /**
     * Лёгкий опрос состояния питания сервера (для polling в UI).
     * GET /api/servers/{id}/state
     */
    public function state(Request $request, $id, PterodactylService $ptero)
    {
        $server = $request->user()->servers()->findOrFail($id);

        if ($server->status !== 'active' || !$server->ptero_server_id) {
            return response()->json(['state' => $server->status]);
        }

        try {
            $stats = $ptero->getServerStats($server->ptero_server_id);
            return response()->json(['state' => $stats['current_state'] ?? 'unknown']);
        } catch (\Throwable $e) {
            return response()->json(['state' => 'unknown']);
        }
    }

    /**
     * Состояние консоли: ресурсы + строки лога.
     * GET /api/servers/{id}/console
     */
    public function console(Request $request, $id, PterodactylService $ptero)
    {
        $server = $request->user()->servers()->findOrFail($id);

        if ($server->status !== 'active' || !$server->ptero_server_id) {
            return response()->json([
                'state'     => $server->status,
                'resources' => null,
                'logs'      => [],
            ]);
        }

        $stats = [];
        $logs  = [];
        try {
            $stats = $ptero->getServerStats($server->ptero_server_id);
            $logs  = $ptero->getConsoleLog($server->ptero_server_id);
        } catch (\Throwable $e) {
            // Не валим консоль из-за ошибки опроса — отдаём что есть.
        }

        return response()->json([
            'state'     => $stats['current_state'] ?? 'unknown',
            'resources' => $stats['resources'] ?? null,
            'logs'      => $logs,
        ]);
    }

    /**
     * Продление сервера: списываем с баланса и сдвигаем expires_at.
     * POST /api/servers/{id}/renew  { days }
     */
    public function renew(Request $request, $id, BillingService $billing, PterodactylService $ptero)
    {
        $request->validate(['days' => 'required|integer|min:1|max:365']);

        $server = $request->user()->servers()->with('tariff')->findOrFail($id);

        if (in_array($server->status, ['deleted'], true)) {
            return response()->json(['error' => 'Этот сервер удалён и не может быть продлён.'], 422);
        }
        if (!$server->tariff) {
            return response()->json(['error' => 'У сервера не задан тариф.'], 422);
        }

        $days   = (int) $request->days;
        $amount = round($server->tariff->price_day * $days, 2);

        try {
            DB::transaction(function () use ($server, $amount, $days, $billing) {
                $billing->charge($server->user_id, $amount, 'Продление сервера #' . $server->id);

                // Продлеваем от текущей даты окончания, если она в будущем, иначе от now().
                $base = $server->expires_at && $server->expires_at->isFuture()
                    ? $server->expires_at
                    : now();

                $server->expires_at = $base->copy()->addDays($days);

                // Возвращаем из приостановки по сроку.
                if ($server->status === 'suspended') {
                    $server->status = 'active';
                }
                $server->save();

                Order::create([
                    'user_id'   => $server->user_id,
                    'server_id' => $server->id,
                    'amount'    => $amount,
                    'type'      => 'renew',
                    'status'    => 'completed',
                ]);
            });
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        // Best-effort снятие приостановки в панели.
        if ($server->ptero_server_id) {
            try { $ptero->unsuspendServer($server->ptero_server_id); } catch (\Throwable $e) {}
        }

        $server->refresh()->load('tariff');
        $server->append('address');

        return response()->json([
            'message' => "Сервер продлён на {$days} дн.",
            'server'  => $server,
        ]);
    }
}
