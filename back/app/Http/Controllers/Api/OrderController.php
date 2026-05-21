<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProvisionServer;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\PromoUse;
use App\Models\Server;
use App\Models\Setting;
use App\Models\Tariff;
use App\Services\BillingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function quote(Request $request)
    {
        $tariff = Tariff::findOrFail($request->tariff_id);
        $days = $request->days ?? 30;
        return response()->json(['total_price' => round($tariff->price_day * $days, 2)]);
    }

    public function index(Request $request)
    {
        return response()->json(
            $request->user()->orders()->with('server.tariff')->latest()->get()
        );
    }

    /**
     * Проверяет промокод и возвращает discount_pct.
     * Эндпоинт: POST /api/promo/apply
     */
    public function applyPromo(Request $request)
    {
        $request->validate(['code' => 'required|string|max:32']);

        $promo = PromoCode::where('code', $request->code)->first();
        if (!$promo) {
            return response()->json(['message' => 'Промокод не найден'], 404);
        }
        if (!$promo->isUsableBy($request->user()->id)) {
            $reason = $promo->expires_at && $promo->expires_at->isPast()
                ? 'Срок действия промокода истёк'
                : 'Промокод недоступен или уже использован';
            return response()->json(['message' => $reason], 422);
        }

        return response()->json([
            'code'         => $promo->code,
            'discount_pct' => $promo->discount_pct,
        ]);
    }

    /**
     * Создание заказа.
     */
    public function store(Request $request, BillingService $billing)
    {
        $request->validate([
            'tariff_id'  => 'required|exists:tariffs,id',
            'days'       => 'required|integer|min:1|max:365',
            'mc_version' => 'required|string|exists:mc_versions,slug',
            'promo_code' => 'nullable|string|max:32',
        ]);

        // Maintenance mode (из таблицы settings).
        if (Setting::get('maintenance_mode', false)) {
            return response()->json([
                'error' => Setting::get('maintenance_message', 'Сервис временно недоступен.'),
            ], 503);
        }

        $user   = $request->user();
        $tariff = Tariff::findOrFail($request->tariff_id);

        // Лимит серверов на юзера.
        $maxServers = (int) Setting::get('max_servers_per_user', 0);
        if ($maxServers > 0) {
            $activeCount = Server::where('user_id', $user->id)
                ->whereNotIn('status', ['deleted'])
                ->count();
            if ($activeCount >= $maxServers) {
                return response()->json([
                    'error' => "Достигнут лимит серверов на аккаунт ({$maxServers}).",
                ], 422);
            }
        }

        // Применение промокода.
        $promo = null;
        $discountPct = 0;
        if ($request->filled('promo_code')) {
            $promo = PromoCode::where('code', $request->promo_code)->first();
            if (!$promo || !$promo->isUsableBy($user->id)) {
                return response()->json(['error' => 'Промокод недоступен'], 422);
            }
            $discountPct = $promo->discount_pct;
        }

        $base   = round($tariff->price_day * $request->days, 2);
        $amount = round($base * (1 - $discountPct / 100), 2);

        $server = null;
        try {
            DB::transaction(function () use ($request, $user, $tariff, $amount, $billing, $promo, &$server) {
                $billing->charge($user->id, $amount, 'Аренда сервера ' . $tariff->name);

                $server = Server::create([
                    'user_id'    => $user->id,
                    'tariff_id'  => $tariff->id,
                    'mc_version' => $request->mc_version,
                    'expires_at' => now()->addDays($request->days),
                    'status'     => 'pending',
                ]);

                $order = Order::create([
                    'user_id'   => $user->id,
                    'server_id' => $server->id,
                    'amount'    => $amount,
                    'type'      => 'new',
                    'status'    => 'completed',
                ]);

                if ($promo) {
                    PromoUse::create([
                        'promo_code_id' => $promo->id,
                        'user_id'       => $user->id,
                        'order_id'      => $order->id,
                        'created_at'    => now(),
                    ]);
                    $promo->increment('used_count');
                }
            });
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        ProvisionServer::dispatch($server);
        $server->refresh()->load('tariff');

        return response()->json([
            'message'   => 'Сервер создаётся, обычно это занимает 1-2 минуты.',
            'server_id' => $server->id,
            'server'    => $server,
        ], 201);
    }
}
