<?php

/**
 * Конфигурация интеграции с Pterodactyl Panel.
 *
 * STUB-режим:
 * Если PTERODACTYL_API_KEY не задан, сервис автоматически переходит
 * в режим заглушки — реальные HTTP запросы к панели не делаются.
 */
return [

    'url' => env('PTERODACTYL_URL', 'http://localhost'),

    // Application API key (Admin → Application API)
    'app_key' => env('PTERODACTYL_API_KEY', ''),

    // Client API key (Account → API Credentials)
    'client_key' => env('PTERODACTYL_CLIENT_KEY', ''),

    // ID ноды по умолчанию (если не задан в БД через таблицу nodes).
    'default_node_id' => (int) env('PTERODACTYL_NODE_ID', 1),

    // Маппинг mc_version → ID Egg в Pterodactyl (значения по умолчанию,
    // основное — таблица mc_versions).
    'eggs' => [
        '1.20.4'        => (int) env('PTERO_EGG_VANILLA', 1),
        'paper_1.20.4'  => (int) env('PTERO_EGG_PAPER', 3),
        'forge_1.20.1'  => (int) env('PTERO_EGG_FORGE', 5),
        'fabric_1.20.4' => (int) env('PTERO_EGG_FABRIC', 7),
    ],

    'docker_image' => env('PTERODACTYL_DOCKER_IMAGE', 'ghcr.io/pterodactyl/yolks:java_17'),

    'startup' => env(
        'PTERODACTYL_STARTUP',
        'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar {{SERVER_JARFILE}} --nogui'
    ),

    'timeout' => (int) env('PTERODACTYL_TIMEOUT', 15),
];
