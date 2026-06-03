<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * ЮKassa (YooKassa) — заготовка боевого шлюза.
 *
 * STUB: пока YOOKASSA_SHOP_ID / YOOKASSA_SECRET_KEY не заданы в .env,
 * методы кидают понятную ошибку (или делегируют на StubGateway через
 * менеджер). Реальные вызовы помечены комментариями // TODO(yookassa).
 *
 * Документация API: https://yookassa.ru/developers/api
 * Создание платежа:  POST https://api.yookassa.ru/v3/payments
 * Аутентификация:    Basic (shopId:secretKey)
 * Idempotence-Key:   обязательный заголовок при создании.
 */
class YooKassaGateway implements PaymentGateway
{
    private string $shopId;
    private string $secretKey;

    public function __construct()
    {
        $this->shopId    = (string) env('YOOKASSA_SHOP_ID', '');
        $this->secretKey = (string) env('YOOKASSA_SECRET_KEY', '');
    }

    public function name(): string
    {
        return 'yookassa';
    }

    public function isConfigured(): bool
    {
        return $this->shopId !== '' && $this->secretKey !== '';
    }

    public function initiate(Payment $payment, array $options = []): array
    {
        if (!$this->isConfigured()) {
            // STUB: ключей нет — не делаем боевой запрос.
            throw new \RuntimeException(
                'ЮKassa не настроена: задайте YOOKASSA_SHOP_ID и YOOKASSA_SECRET_KEY в .env'
            );
        }

        $returnUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/')
            . '/payment/result?payment_id=' . $payment->id;

        $http = Http::withBasicAuth($this->shopId, $this->secretKey)
            ->withHeaders(['Idempotence-Key' => (string) Str::uuid()])
            ->acceptJson()
            ->post('https://api.yookassa.ru/v3/payments', [
                'amount'       => [
                    'value'    => number_format((float) $payment->amount, 2, '.', ''),
                    'currency' => 'RUB',
                ],
                'capture'      => true,
                'confirmation' => [
                    'type'       => 'redirect',
                    'return_url' => $returnUrl,
                ],
                'description'  => "Пополнение баланса CraftHost #{$payment->id}",
                'metadata'     => [
                    'payment_id' => $payment->id,
                    'user_id'    => $payment->user_id,
                ],
            ]);

        // Боевой запрос завершился ошибкой — отдаём человекочитаемое сообщение,
        // а полную информацию пишем в лог для администратора.
        if ($http->failed()) {
            $err  = $http->json();
            $code = $err['code'] ?? null;
            $desc = $err['description'] ?? '';

            Log::error('YooKassa initiate failed', [
                'http_status' => $http->status(),
                'shop_id'     => $this->shopId,
                'payment_id'  => $payment->id,
                'response'    => $err,
            ]);

            $message = match ($code) {
                'forbidden'       => 'Оплата картой временно недоступна: магазин ЮKassa ещё не активирован для приёма платежей. Обратитесь в поддержку.',
                'invalid_request' => 'ЮKassa отклонила запрос на оплату. Попробуйте позже или свяжитесь с поддержкой.',
                default           => 'Не удалось создать платёж ЮKassa' . ($desc ? ": {$desc}" : '. Попробуйте позже.'),
            };

            throw new \RuntimeException($message);
        }

        $response = $http->json();

        $payment->update(['external_id' => $response['id'] ?? null]);

        return [
            'payment_id'       => $payment->id,
            'confirmation_url' => $response['confirmation']['confirmation_url'] ?? '',
            'external_id'      => $response['id'] ?? null,
        ];
    }

    public function handleWebhook(Request $request): array
    {
        // TODO(yookassa): проверка подлинности (IP allowlist ЮKassa / подпись).
        // Формат уведомления: { event, object: { id, status, metadata, ... } }
        $object = $request->input('object', []);
        $externalId = $object['id'] ?? null;
        $status     = $object['status'] ?? 'pending';

        $payment = null;
        if (isset($object['metadata']['payment_id'])) {
            $payment = Payment::find($object['metadata']['payment_id']);
        } elseif ($externalId) {
            $payment = Payment::where('external_id', $externalId)->first();
        }

        // Нормализуем статусы ЮKassa в наши.
        $normalized = match ($status) {
            'succeeded' => 'success',
            'canceled'  => 'canceled',
            default     => 'pending',
        };

        return [
            'payment'     => $payment,
            'status'      => $normalized,
            'external_id' => $externalId,
        ];
    }

    public function checkStatus(Payment $payment): string
    {
        if (!$this->isConfigured() || !$payment->external_id) {
            return $payment->status;
        }

        // TODO(yookassa): GET /v3/payments/{payment_id}
        $resp = Http::withBasicAuth($this->shopId, $this->secretKey)
            ->acceptJson()
            ->get("https://api.yookassa.ru/v3/payments/{$payment->external_id}")
            ->throw()->json();

        return match ($resp['status'] ?? 'pending') {
            'succeeded' => 'success',
            'canceled'  => 'canceled',
            default     => 'pending',
        };
    }
}
