<?php

namespace App\Services\Payments;

/**
 * Менеджер платёжных шлюзов.
 *
 * Отдаёт нужную реализацию PaymentGateway по имени провайдера.
 * Логика выбора:
 *   - если запрошен конкретный провайдер и он сконфигурирован — отдаём его;
 *   - если провайдер не настроен (нет ключей) — фолбэк на StubGateway,
 *     чтобы флоу пополнения работал в деве без реальных платёжек.
 *
 * Добавление нового провайдера = новый класс + одна строка в $map.
 */
class PaymentGatewayManager
{
    /** Список доступных провайдеров (имя => класс). */
    private array $map = [
        'stub'        => StubGateway::class,
        'yookassa'    => YooKassaGateway::class,
        'crypto_usdt' => CryptoUsdtGateway::class,
    ];

    /**
     * Получить шлюз по имени. null/'' -> провайдер по умолчанию из конфига.
     */
    public function get(?string $name = null): PaymentGateway
    {
        $name = $name ?: (string) config('payments.default', 'stub');

        $class = $this->map[$name] ?? null;
        if (!$class) {
            // Неизвестный провайдер — безопасный фолбэк на stub.
            return app(StubGateway::class);
        }

        $gateway = app($class);

        // Если боевой провайдер не сконфигурирован — фолбэк на stub,
        // чтобы дев-флоу не падал.
        if (method_exists($gateway, 'isConfigured') && !$gateway->isConfigured()) {
            return app(StubGateway::class);
        }

        return $gateway;
    }

    /** Список провайдеров, доступных пользователю (для выбора на фронте). */
    public function available(): array
    {
        $result = [];
        foreach ($this->map as $name => $class) {
            $gateway = app($class);
            $configured = !method_exists($gateway, 'isConfigured') || $gateway->isConfigured();
            // stub всегда доступен в деве; боевые — только если настроены.
            if ($name === 'stub') {
                $result[] = ['name' => 'stub', 'label' => 'Тестовая оплата', 'configured' => true];
            } elseif ($configured) {
                $result[] = [
                    'name'       => $name,
                    'label'      => $this->labelFor($name),
                    'configured' => true,
                ];
            }
        }
        return $result;
    }

    private function labelFor(string $name): string
    {
        return [
            'yookassa'      => 'Карта (ЮKassa)',
            'crypto_usdt'   => 'USDT (Крипто)',
            'stripe'        => 'Stripe',
            'cloudpayments' => 'CloudPayments',
        ][$name] ?? ucfirst($name);
    }
}
