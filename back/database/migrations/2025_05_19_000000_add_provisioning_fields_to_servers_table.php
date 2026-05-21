<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Расширяем enum статуса: добавляем pending/provisioning/error.
        // Делаем raw SQL, т.к. doctrine/dbal не умеет менять enum в MySQL.
        // Оборачиваем в try/catch — на sqlite (тесты) enum нет, это нормально.
        try {
            \Illuminate\Support\Facades\DB::statement(
                "ALTER TABLE `servers` MODIFY COLUMN `status` "
                . "enum('pending','provisioning','active','suspended','deleted','error') "
                . "NOT NULL DEFAULT 'pending'"
            );
        } catch (\Throwable $e) {
            // Не MySQL или колонка уже подходящая — пропускаем.
        }

        Schema::table('servers', function (Blueprint $table) {
            if (!Schema::hasColumn('servers', 'server_ip')) {
                $table->string('server_ip', 45)->nullable()->after('ptero_server_id');
            }
            if (!Schema::hasColumn('servers', 'server_port')) {
                $table->unsignedSmallInteger('server_port')->nullable()->after('server_ip');
            }
            if (!Schema::hasColumn('servers', 'sftp_password')) {
                $table->string('sftp_password', 64)->nullable()->after('server_port');
            }
            if (!Schema::hasColumn('servers', 'provisioning_error')) {
                $table->text('provisioning_error')->nullable()->after('sftp_password');
            }
            if (!Schema::hasColumn('servers', 'node_id')) {
                $table->unsignedBigInteger('node_id')->nullable()->after('tariff_id');
                // FK добавим в миграции nodes (она идёт после)
            }
        });
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            foreach (['server_ip', 'server_port', 'sftp_password', 'provisioning_error', 'node_id'] as $col) {
                if (Schema::hasColumn('servers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
