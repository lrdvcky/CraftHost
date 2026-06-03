<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Создаёт все базовые таблицы проекта CraftHost.
 * Должна выполняться ДО add_provisioning_fields_to_servers_table.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── Тарифы ──────────────────────────────────────────
        Schema::create('tariffs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedInteger('ram_mb');
            $table->unsignedInteger('cpu_percent')->default(100);
            $table->unsignedInteger('disk_mb')->default(10240);
            $table->unsignedInteger('slots')->default(20);
            $table->decimal('price_day', 10, 2);
            $table->timestamps();
        });

        // ── Серверы ─────────────────────────────────────────
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tariff_id')->constrained()->cascadeOnDelete();
            $table->string('ptero_server_id')->nullable();
            $table->string('mc_version', 32)->default('1.20.4');
            $table->enum('status', ['pending', 'provisioning', 'active', 'suspended', 'deleted', 'error'])
                  ->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        // ── Заказы ──────────────────────────────────────────
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('type')->default('new');           // new / renew
            $table->string('status')->default('completed');   // pending / completed / failed
            $table->timestamp('created_at')->nullable();
        });

        // ── Платежи ─────────────────────────────────────────
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('provider')->default('balance');   // balance / yookassa / etc
            $table->string('external_id')->nullable();
            $table->string('status')->default('completed');
            $table->timestamp('created_at')->nullable();
        });

        // ── Тикеты поддержки ────────────────────────────────
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('subject');
            $table->string('status')->default('open');        // open / answered / closed
            $table->unsignedBigInteger('assigned_admin_id')->nullable();
            $table->timestamps();

            $table->foreign('assigned_admin_id')->references('id')->on('users')->nullOnDelete();
        });

        // ── Сообщения тикетов ───────────────────────────────
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamp('created_at')->nullable();
        });

        // ── Бэкапы ─────────────────────────────────────────
        Schema::create('backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('ptero_backup_id')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamp('created_at')->nullable();
        });

        // ── Моды серверов ───────────────────────────────────
        Schema::create('server_mods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->timestamp('uploaded_at')->nullable();
        });

        // ── Реферальные коды ────────────────────────────────
        Schema::create('referral_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code')->unique();
            $table->timestamp('created_at')->nullable();
        });

        // ── Реферальные комиссии ────────────────────────────
        Schema::create('referral_commissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('referrer_id');
            $table->unsignedBigInteger('referred_id');
            $table->decimal('amount', 10, 2);
            $table->timestamp('created_at')->nullable();

            $table->foreign('referrer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('referred_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // promo_uses создаётся после promo_codes (миграция 2025_05_19_010200)
    }

    public function down(): void
    {
        Schema::dropIfExists('referral_commissions');
        Schema::dropIfExists('referral_codes');
        Schema::dropIfExists('server_mods');
        Schema::dropIfExists('backups');
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('servers');
        Schema::dropIfExists('tariffs');
    }
};
