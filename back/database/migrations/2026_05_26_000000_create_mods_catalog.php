<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('kind', ['mod', 'plugin']);
            $table->enum('loader', ['forge', 'fabric', 'paper', 'spigot', 'bukkit']);
            $table->json('mc_versions')->nullable();
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('filename');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('icon_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['kind', 'loader', 'is_active']);
        });

        Schema::table('server_mods', function (Blueprint $table) {
            $table->foreignId('mod_id')->nullable()->after('server_id')
                ->constrained('mods')->nullOnDelete();
            $table->enum('status', ['installing', 'installed', 'failed', 'removing'])
                ->default('installed')->after('size_bytes');
            $table->string('error')->nullable()->after('status');
            $table->timestamp('installed_at')->nullable()->after('error');
        });
    }

    public function down(): void
    {
        Schema::table('server_mods', function (Blueprint $table) {
            $table->dropForeign(['mod_id']);
            $table->dropColumn(['mod_id', 'status', 'error', 'installed_at']);
        });
        Schema::dropIfExists('mods');
    }
};
