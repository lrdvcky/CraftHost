<?php

namespace App\Services\Payments;

use App\Models\Payment;

/**
 * Контракт платёжного шлюза.
 *
 * Любая платёжная система (ЮKassa, Stripe, CloudPayments, Робокасса...)
 * реализует этот интерфейс. Контроллер и остальной код работают только
 * с этим контрактом и не знают деталей конкретного провайдера.
 */
interface PaymentGateway
{
    /**
     * Машинное имя провайдера ('yookassa', 'stripe', 'stub', ...).
     * Совпадает со значением, которое пишется в payments.provider.
     */
    public function name(): string;

    /**
     * Инициализировать платёж.
     *
     * Создаёт платёж на стороне провайдера и возвращает данные для
     * перенаправления пользователя на оплату.
     *
     * @return array{
     *   payment_id: int,        // ID нашей записи Payment
     *   confirmation_url: string, // куда редиректить пользователя
     *   external_id: ?string    // ID платежа на стороне провайдера
     * }
     */
    public function initiate(Payment $payment, array $options = []): array;

    /**
     * Обработать webhook/callback от провайдера.
     *
     * Должен:
     *  - проверить подпись/подлинность запроса;
     *  - найти соответствующий Payment;
     *  - вернуть нормализованный результат.
     *
     * @return array{
     *   payment: ?Payment,
     *   status: string,   // 'success' | 'canceled' | 'pending'
     *   external_id: ?string
     * }
     */
    public function handleWebhook(\Illuminate\Http\Request $request): array;

    /**
     * Проверить статус платежа напрямую у провайдера (polling-фолбэк,
     * если webhook не пришёл).
     *
     * @return string 'success' | 'canceled' | 'pending'
     */
    public function checkStatus(Payment $payment): string;
}
