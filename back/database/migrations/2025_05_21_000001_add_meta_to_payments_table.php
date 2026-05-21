<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (!Schema::hasColumn('payments', 'meta')) {
                $table->text('meta')->nullable()->after('status');
            }
            if (!Schema::hasColumn('payments', 'confirmation_url')) {
                $table->string('confirmation_url', 1024)->nullable()->after('external_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['meta', 'confirmation_url']);
        });
    }
};
