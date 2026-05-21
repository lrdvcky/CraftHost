<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ServerController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\ReferralController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\TariffController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\McVersionController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\PromoCodeController;
use App\Http\Controllers\Api\NodeController;
use App\Http\Controllers\Api\AdminTicketController;
use App\Http\Controllers\Api\FaqController;

// ---------------- Публичные ----------------
Route::post('/register',      [AuthController::class, 'register']);
Route::post('/login',         [AuthController::class, 'login']);
Route::post('/orders/quote',  [OrderController::class, 'quote']);
Route::get('/tariffs',        [TariffController::class, 'index']);
Route::get('/mc-versions',    [McVersionController::class, 'index']);
Route::get('/public-settings',[SettingController::class, 'publicIndex']);
Route::get('/faqs',            [FaqController::class, 'index']);

Route::post('/forgot-password',   [AuthController::class, 'forgotPassword']);
Route::post('/reset-password',    [AuthController::class, 'resetPassword']);
Route::post('/email/verify',      [AuthController::class, 'verifyEmail']);

// Webhook платёжных систем — публичный (без auth), подлинность проверяет шлюз.
Route::post('/payments/webhook/{provider}', [PaymentController::class, 'webhook']);

// ---------------- Авторизованные ----------------
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user',     [AuthController::class, 'user']);
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::post('/email/send-verification', [AuthController::class, 'sendVerification']);

    Route::get('/tariffs/{id}', [TariffController::class, 'show']);

    Route::post('/orders',       [OrderController::class, 'store']);
    Route::get('/orders',        [OrderController::class, 'index']);
    Route::post('/promo/apply',  [OrderController::class, 'applyPromo']);

    Route::prefix('servers')->group(function () {
        Route::get('/',               [ServerController::class, 'index']);
        Route::get('/{id}',           [ServerController::class, 'show']);
        Route::post('/{id}/power',    [ServerController::class, 'power']);
        Route::post('/{id}/command',  [ServerController::class, 'command']);
        Route::get('/{id}/console',   [ServerController::class, 'console']);
        Route::post('/{id}/renew',    [ServerController::class, 'renew']);
        Route::get('/{id}/backups',                    [BackupController::class, 'index']);
        Route::post('/{id}/backups',                   [BackupController::class, 'store']);
        Route::post('/{id}/backups/{backupId}/restore',[BackupController::class, 'restore']);
        Route::post('/{id}/regen-map',                 [ServerController::class, 'regenMap']);
    });

    Route::prefix('tickets')->group(function () {
        Route::get('/',                 [TicketController::class, 'index']);
        Route::post('/',                [TicketController::class, 'store']);
        Route::get('/{id}/messages',    [TicketController::class, 'messages']);
        Route::post('/{id}/reply',      [TicketController::class, 'reply']);
        Route::patch('/{id}/close',     [TicketController::class, 'close']);
    });

    Route::prefix('referral')->group(function () {
        Route::get('/',          [ReferralController::class, 'index']);
        Route::post('/generate', [ReferralController::class, 'generate']);
    });

    Route::post('/payments/topup',    [PaymentController::class, 'topup']);
    Route::get('/payments/history',   [PaymentController::class, 'history']);
    Route::get('/payments/methods',   [PaymentController::class, 'methods']);
    Route::post('/payments/initiate', [PaymentController::class, 'initiate']);
    Route::post('/payments/{id}/confirm-stub', [PaymentController::class, 'confirmStub']);
    Route::get('/payments/{id}/status',        [PaymentController::class, 'status']);
    Route::get('/payments/{id}/crypto-details',[PaymentController::class, 'cryptoDetails']);
    Route::post('/payments/{id}/cancel',       [PaymentController::class, 'cancel']);

    Route::prefix('notifications')->group(function () {
        Route::get('/',                 [NotificationController::class, 'index']);
        Route::get('/unread-count',     [NotificationController::class, 'unreadCount']);
        Route::post('/read-all',        [NotificationController::class, 'markAllRead']);
        Route::post('/{id}/read',       [NotificationController::class, 'markRead']);
    });

    // ---------------- Админка ----------------
    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/dashboard',                  [AdminController::class, 'dashboard']);
        Route::get('/users',                      [AdminController::class, 'users']);
        Route::put('/users/{id}',                 [AdminController::class, 'updateUser']);
        Route::post('/users/{id}/verify-email',  [AdminController::class, 'verifyUserEmail']);
        Route::get('/audit',                      [AdminController::class, 'auditLog']);
        Route::get('/servers',                    [AdminController::class, 'servers']);
        Route::post('/servers/{id}/suspend',      [AdminController::class, 'suspendServer']);
        Route::post('/servers/{id}/unsuspend',    [AdminController::class, 'unsuspendServer']);
        Route::put('/servers/{id}/tariff',        [AdminController::class, 'changeTariff']);
        Route::delete('/servers/{id}',            [AdminController::class, 'deleteServer']);

        // Тарифы
        Route::get('/tariffs',                    [TariffController::class, 'adminIndex']);
        Route::post('/tariffs',                   [TariffController::class, 'store']);
        Route::put('/tariffs/{id}',               [TariffController::class, 'update']);
        Route::delete('/tariffs/{id}',            [TariffController::class, 'destroy']);

        // Версии Minecraft
        Route::get('/mc-versions',                [McVersionController::class, 'adminIndex']);
        Route::post('/mc-versions',               [McVersionController::class, 'store']);
        Route::put('/mc-versions/{id}',           [McVersionController::class, 'update']);
        Route::delete('/mc-versions/{id}',        [McVersionController::class, 'destroy']);

        // Промокоды
        Route::get('/promo',                      [PromoCodeController::class, 'index']);
        Route::post('/promo',                     [PromoCodeController::class, 'store']);
        Route::put('/promo/{id}',                 [PromoCodeController::class, 'update']);
        Route::delete('/promo/{id}',              [PromoCodeController::class, 'destroy']);

        // Ноды
        Route::get('/nodes',                      [NodeController::class, 'index']);
        Route::post('/nodes',                     [NodeController::class, 'store']);
        Route::put('/nodes/{id}',                 [NodeController::class, 'update']);
        Route::delete('/nodes/{id}',              [NodeController::class, 'destroy']);

        // Настройки
        Route::get('/settings',                   [SettingController::class, 'adminIndex']);
        Route::put('/settings/{key}',             [SettingController::class, 'update']);

        // FAQ
        Route::get('/faqs',                       [FaqController::class, 'adminIndex']);
        Route::post('/faqs',                      [FaqController::class, 'store']);
        Route::put('/faqs/{id}',                  [FaqController::class, 'update']);
        Route::delete('/faqs/{id}',               [FaqController::class, 'destroy']);

        // Тикеты (админ видит и отвечает на ВСЕ)
        Route::get('/tickets',                    [AdminTicketController::class, 'index']);
        Route::get('/tickets/{id}',               [AdminTicketController::class, 'show']);
        Route::post('/tickets/{id}/reply',        [AdminTicketController::class, 'reply']);
        Route::post('/tickets/{id}/assign',       [AdminTicketController::class, 'assign']);
        Route::patch('/tickets/{id}/status',      [AdminTicketController::class, 'setStatus']);
    });
});
