<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nodes')) {
            Schema::table('nodes', function (Blueprint $table) {
                if (!Schema::hasColumn('nodes', 'is_active')) $table->boolean('is_active')->default(true)->after('max_servers');
                if (!Schema::hasColumn('nodes', 'fqdn'))      $table->string('fqdn', 255)->nullable()->after('location');
            });
        } else {
            Schema::create('nodes', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100);
                $table->unsignedInteger('ptero_node_id')->nullable();
                $table->string('location', 100)->nullable(); // ru-mow1, de-fra1, ...
                $table->string('fqdn', 255)->nullable();
                $table->unsignedInteger('max_servers')->default(0); // 0 = без лимита
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->nullable();
            });
        }

        // FK servers.node_id → nodes.id (миграция servers.node_id уже создала
        // колонку без FK; добавляем здесь, когда таблица nodes точно есть).
        if (Schema::hasColumn('servers', 'node_id')) {
            try {
                Schema::table('servers', function (Blueprint $table) {
                    $table->foreign('node_id')
                        ->references('id')->on('nodes')
                        ->nullOnDelete();
                });
            } catch (\Throwable $e) {
                // FK уже существует — игнорируем.
            }
        }
    }

    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            try { $table->dropForeign(['node_id']); } catch (\Throwable $e) {}
        });
        Schema::dropIfExists('nodes');
    }
};
