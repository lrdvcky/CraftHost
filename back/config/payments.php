<?php

/**
 * Конфигурация платёжных систем.
 *
 * default — провайдер по умолчанию, если фронт не указал явно.
 * Пока боевые провайдеры не настроены (нет ключей в .env),
 * PaymentGatewayManager автоматически откатывается на 'stub'.
 */
return [

    'default' => env('PAYMENTS_DEFAULT', 'stub'),

    // Зачислять баланс сразу при stub-оплате (удобно для разработки).
    'stub_autocomplete' => filter_var(env('PAYMENTS_STUB_AUTOCOMPLETE', true), FILTER_VALIDATE_BOOLEAN),

    'yookassa' => [
        'shop_id'    => env('YOOKASSA_SHOP_ID', ''),
        'secret_key' => env('YOOKASSA_SECRET_KEY', ''),
    ],

    // === USDT direct crypto payments ===
    'crypto_usdt' => [
        'rubToUsdt'         => (float) env('CRYPTO_RUB_TO_USDT', 95.0),
        'invoiceTtlMinutes' => 60,
        'minConfirmations'  => 1,
        'networks' => [
            'bep20' => [
                'enabled'   => true,
                'label'     => 'BEP-20',
                'fullLabel' => 'BNB Smart Chain (BEP-20)',
                'wallet'    => env('CRYPTO_WALLET_BEP20', '0x7db6ae87b04b2b72d9fefc6f392d8d94718aec74'),
                'contract'  => '0x55d398326f99059ff775485246999027b3197955',
                'decimals'  => 18,
                'rpcUrls'   => [
                    'https://bsc-rpc.publicnode.com',
                    'https://bsc-dataseed.binance.org',
                ],
                'time'           => '~15 сек',
                'lookbackBlocks' => 2400,
                'kind'           => 'evm',
            ],
            'erc20' => [
                'enabled'   => true,
                'label'     => 'ERC-20',
                'fullLabel' => 'Ethereum (ERC-20)',
                'wallet'    => env('CRYPTO_WALLET_ERC20', '0x7db6ae87b04b2b72d9fefc6f392d8d94718aec74'),
                'contract'  => '0xdAC17F958D2ee523a2206206994597C13D831ec7',
                'decimals'  => 6,
                'rpcUrls'   => [
                    'https://ethereum-rpc.publicnode.com',
                    'https://eth.llamarpc.com',
                ],
                'time'           => '~3-5 минут',
                'lookbackBlocks' => 600,
                'kind'           => 'evm',
            ],
            'trc20' => [
                'enabled'   => true,
                'label'     => 'TRC-20',
                'fullLabel' => 'TRON (TRC-20)',
                'wallet'    => env('CRYPTO_WALLET_TRC20', 'TM7Yap9nerWHTgEPsGf9VGbriR2mv48mTu'),
                'contract'  => 'TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t',
                'decimals'  => 6,
                'apiUrl'    => 'https://api.trongrid.io',
                'time'      => '~1 минута',
                'kind'      => 'tron',
            ],
        ],
    ],
];
