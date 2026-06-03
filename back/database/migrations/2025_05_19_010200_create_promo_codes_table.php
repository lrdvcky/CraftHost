<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('promo_codes')) {
            Schema::create('promo_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code', 32)->unique();
                $table->unsignedTinyInteger('discount_pct'); // 1..100
                $table->unsignedInteger('max_uses')->default(0); // 0 = безлимит
                $table->unsignedInteger('used_count')->default(0);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('promo_uses')) {
            Schema::create('promo_uses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('promo_code_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
                $table->timestamp('created_at')->nullable();

                // Один и тот же юзер не может использовать один и тот же код дважды.
                $table->unique(['promo_code_id', 'user_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_uses');
        Schema::dropIfExists('promo_codes');
    }
};
