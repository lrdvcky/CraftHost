<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mc_versions')) {
            // На случай если таблица существовала из старых миграций — добавим
            // недостающие поля.
            Schema::table('mc_versions', function (Blueprint $table) {
                if (!Schema::hasColumn('mc_versions', 'slug'))            $table->string('slug', 64)->unique()->after('id');
                if (!Schema::hasColumn('mc_versions', 'ptero_egg_id'))    $table->unsignedInteger('ptero_egg_id')->nullable()->after('jar_url');
                if (!Schema::hasColumn('mc_versions', 'is_active'))       $table->boolean('is_active')->default(true)->after('ptero_egg_id');
                if (!Schema::hasColumn('mc_versions', 'sort_order'))      $table->unsignedInteger('sort_order')->default(0)->after('is_active');
            });
            return;
        }

        Schema::create('mc_versions', function (Blueprint $table) {
            $table->id();
            // slug — ключ, по которому фронт шлёт mc_version (1.20.4, paper_1.20.4, ...).
            $table->string('slug', 64)->unique();
            // label — то, что увидит пользователь ("Vanilla 1.20.4").
            $table->string('label', 128);
            // type: vanilla | paper | forge | fabric | spigot ...
            $table->string('type', 32);
            // jar_url — откуда брать .jar (опционально).
            $table->string('jar_url', 512)->nullable();
            // ID egg в Pterodactyl Panel.
            $table->unsignedInteger('ptero_egg_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mc_versions');
    }
};
