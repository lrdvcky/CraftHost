<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Подстраховочная миграция: гарантируем существование таблиц
 * notifications и audit_log (если их по какой-то причине нет).
 * Модели Notification и AuditLog уже определены, но используются
 * слабо — этот файл фиксирует схему.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('type', 64);   // server_ready | server_error | payment_received | ...
                $table->json('data')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index(['user_id', 'read_at']);
            });
        }

        if (!Schema::hasTable('audit_log')) {
            Schema::create('audit_log', function (Blueprint $table) {
                $table->id();
                $table->foreignId('admin_id')->constrained('users')->cascadeOnDelete();
                $table->string('action', 64);
                $table->string('target_type', 32)->nullable();
                $table->unsignedBigInteger('target_id')->nullable();
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->nullable();
                $table->index(['target_type', 'target_id']);
                $table->index(['admin_id', 'created_at']);
            });
        } else {
            // Добавляем meta-поле если его нет (для расширенной информации).
            Schema::table('audit_log', function (Blueprint $table) {
                if (!Schema::hasColumn('audit_log', 'meta')) {
                    $table->json('meta')->nullable()->after('target_id');
                }
            });
        }
    }

    public function down(): void
    {
        // Не удаляем audit_log/notifications вниз — это не наша таблица
        // (могла существовать до этой миграции). Если действительно надо
        // удалить — делайте это вручную.
    }
};
