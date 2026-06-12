<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('name');
            $table->text('description')->nullable()->after('tagline');
            $table->json('features')->nullable()->after('description');
            $table->longText('image')->nullable()->after('features'); // data-URL картинки тарифа
            $table->boolean('is_popular')->default(false)->after('image');
        });
    }

    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn(['tagline', 'description', 'features', 'image', 'is_popular']);
        });
    }
};
