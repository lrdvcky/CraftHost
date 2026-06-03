<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * STUB платёжный шлюз.
 *
 * Не ходит ни в какую реальную платёжную систему. Используется, пока
 * не подключены боевые провайдеры (ЮKassa/Stripe). Имитирует флоу:
 *
 *   initiate() -> возвращает confirmation_url, указывающий на наш же
 *                 фронтовый "эмулятор оплаты" (или сразу помечает success,
 *                 если PAYMENTS_STUB_AUTOCOMPLETE=true).
 *
 * Когда подключите реальный провайдер — просто зарегистрируйте его в
 * PaymentGatewayManager, фронт менять не придётся.
 */
class StubGateway implements PaymentGateway
{
    public function name(): string
    {
        return 'stub';
    }

    public function initiate(Payment $payment, array $options = []): array
    {
        // STUB: реальный провайдер вернул бы свой confirmation_url.
        // Мы генерируем фейковый external_id и ссылку на наш фронт,
        // где пользователь нажмёт "Оплатить" (эмуляция).
        $externalId = 'stub_' . Str::lower(Str::random(16));
        $payment->update(['external_id' => $externalId]);

        $autocomplete = filter_var(env('PAYMENTS_STUB_AUTOCOMPLETE', true), FILTER_VALIDATE_BOOLEAN);

        Log::info('[PAY STUB] initiate', [
            'payment_id'  => $payment->id,
            'amount'      => $payment->amount,
            'external_id' => $externalId,
            'autocomplete'=> $autocomplete,
        ]);

        // confirmation_url ведёт на фронт; параметр payment_id фронт
        // использует для подтверждения через /payments/{id}/confirm-stub.
        $frontUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');

        return [
            'payment_id'       => $payment->id,
            'confirmation_url' => "{$frontUrl}/payment/process?payment_id={$payment->id}&provider=stub",
            'external_id'      => $externalId,
        ];
    }

    public function handleWebhook(Request $request): array
    {
        // STUB: «вебхук» — это наш собственный confirm-эндпоинт.
        // Подписи нет; просто доверяем payment_id.
        $payment = Payment::find($request->input('payment_id'));

        return [
            'payment'     => $payment,
            'status'      => $request->input('status', 'success'),
            'external_id' => $payment?->external_id,
        ];
    }

    public function checkStatus(Payment $payment): string
    {
        // STUB: всегда считаем успешным после инициации.
        return $payment->status === 'pending' ? 'success' : $payment->status;
    }
}
