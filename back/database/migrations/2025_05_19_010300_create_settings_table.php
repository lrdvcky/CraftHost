<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('settings')) return;

        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            // type: string | int | bool | float | json — для типизированного чтения.
            $table->string('type', 16)->default('string');
            $table->string('description', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
