<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\BillingService;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /** GET /api/payments/methods — доступные способы оплаты */
    public function methods(PaymentGatewayManager $gateways)
    {
        return response()->json($gateways->available());
    }

    /**
     * POST /api/payments/topup  (СТАРЫЙ прямой способ, оставлен для совместимости)
     * Мгновенно зачисляет баланс.
     */
    public function topup(Request $request, BillingService $billing, ReferralService $referral)
    {
        $min = (int) Setting::get('min_topup_amount', 50);

        $request->validate([
            'amount' => "required|numeric|min:{$min}",
        ], [
            'amount.min' => "Минимальная сумма пополнения — {$min} ₽",
        ]);

        $user = $request->user();

        $payment = Payment::create([
            'user_id'  => $user->id,
            'amount'   => $request->amount,
            'provider' => 'manual',
            'status'   => 'success',
        ]);

        $billing->refund($user->id, $request->amount);
        $referral->processCommission($user->id, $request->amount);

        return response()->json([
            'message'    => 'Баланс пополнен на ' . $request->amount . ' ₽',
            'balance'    => $user->fresh()->balance,
            'payment_id' => $payment->id,
        ]);
    }

    /**
     * POST /api/payments/initiate
     * Создаём pending-платёж и отдаём confirmation_url.
     * body: { amount, provider? }
     */
    public function initiate(Request $request, PaymentGatewayManager $gateways)
    {
        $min = (int) Setting::get('min_topup_amount', 50);

        $request->validate([
            'amount'   => "required|numeric|min:{$min}",
            'provider' => 'nullable|string',
        ], [
            'amount.min' => "Минимальная сумма пополнения — {$min} ₽",
        ]);

        $gateway = $gateways->get($request->provider);

        $payment = Payment::create([
            'user_id'  => $request->user()->id,
            'amount'   => $request->amount,
            'provider' => $gateway->name(),
            'status'   => 'pending',
        ]);

        try {
            $result = $gateway->initiate($payment);
        } catch (\Throwable $e) {
            $payment->update(['status' => 'canceled']);
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json([
            'payment_id'       => $result['payment_id'],
            'confirmation_url' => $result['confirmation_url'],
            'provider'         => $gateway->name(),
        ], 201);
    }

    /**
     * POST /api/payments/{id}/confirm-stub
     * Эмуляция оплаты для StubGateway.
     */
    public function confirmStub(Request $request, $id, BillingService $billing, ReferralService $referral)
    {
        $payment = Payment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('provider', 'stub')
            ->firstOrFail();

        if ($payment->status === 'success') {
            return response()->json(['message' => 'Уже оплачено', 'balance' => $request->user()->fresh()->balance]);
        }

        $this->markPaidAndCredit($payment, $billing, $referral);

        return response()->json([
            'message' => 'Оплата прошла успешно',
            'balance' => $request->user()->fresh()->balance,
        ]);
    }

    /**
     * POST /api/payments/webhook/{provider}
     * Приём уведомлений от платёжных систем. Публичный, подлинность
     * проверяет сам шлюз внутри handleWebhook().
     */
    public function webhook(Request $request, string $provider, PaymentGatewayManager $gateways, BillingService $billing, ReferralService $referral)
    {
        $gateway = $gateways->get($provider);

        try {
            $result = $gateway->handleWebhook($request);
        } catch (\Throwable $e) {
            Log::warning("Payment webhook error [{$provider}]: " . $e->getMessage());
            return response()->json(['error' => 'invalid'], 400);
        }

        $payment = $result['payment'] ?? null;
        if (!$payment) {
            return response()->json(['error' => 'payment not found'], 404);
        }

        if ($result['status'] === 'success' && $payment->status !== 'success') {
            $this->markPaidAndCredit($payment, $billing, $referral);
        } elseif ($result['status'] === 'canceled') {
            $payment->update(['status' => 'canceled']);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * GET /api/payments/{id}/status
     * Фронт опрашивает после редиректа.
     */
    public function status(Request $request, $id, PaymentGatewayManager $gateways, BillingService $billing, ReferralService $referral)
    {
        $payment = Payment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if ($payment->status === 'pending') {
            try {
                $real = $gateways->get($payment->provider)->checkStatus($payment);
                if ($real === 'success') {
                    $this->markPaidAndCredit($payment, $billing, $referral);
                } elseif ($real === 'canceled') {
                    $payment->update(['status' => 'canceled']);
                }
            } catch (\Throwable $e) {
                // Оставляем pending.
            }
        }

        return response()->json([
            'status'  => $payment->fresh()->status,
            'balance' => $request->user()->fresh()->balance,
        ]);
    }

    /**
     * GET /api/payments/{id}/crypto-details
     * Returns USDT payment info for the crypto payment page.
     */
    public function cryptoDetails(Request $request, $id)
    {
        $payment = Payment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        if (!in_array($payment->provider, ['crypto_usdt', 'usdt'])) {
            return response()->json(['error' => 'Not a crypto payment'], 422);
        }

        $meta = $payment->meta ?? [];

        return response()->json([
            'id'          => $payment->id,
            'status'      => $payment->status,
            'amount_rub'  => $payment->amount,
            'amount_usdt' => $meta['amount_usdt'] ?? $payment->external_id,
            'networks'    => $meta['networks'] ?? [],
            'invoice_ttl' => $meta['invoice_ttl'] ?? 60,
            'created_ts'  => $meta['created_ts'] ?? ($payment->created_at?->timestamp ?? time()),
        ]);
    }

    /**
     * POST /api/payments/{id}/cancel
     * Cancel a pending payment.
     */
    public function cancel(Request $request, $id)
    {
        $payment = Payment::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'pending')
            ->firstOrFail();

        $payment->update([
            'status' => 'canceled',
            'meta'   => array_merge($payment->meta ?? [], ['cancel_reason' => 'user', 'cancel_at' => time()]),
        ]);

        return response()->json(['message' => 'Платёж отменён']);
    }

    public function history(Request $request)
    {
        $payments = Payment::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($payments);
    }

    /**
     * Помечаем платёж успешным и зачисляем баланс — атомарно и идемпотентно.
     */
    private function markPaidAndCredit(Payment $payment, BillingService $billing, ReferralService $referral): void
    {
        DB::transaction(function () use ($payment, $billing, $referral) {
            $fresh = Payment::lockForUpdate()->find($payment->id);
            if ($fresh->status === 'success') {
                return;
            }
            $fresh->update(['status' => 'success']);
            $billing->refund($fresh->user_id, (float) $fresh->amount);
            $referral->processCommission($fresh->user_id, (float) $fresh->amount);
        });
    }
}
