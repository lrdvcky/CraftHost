<?php
namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        PromoCode::updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'discount_pct' => 10,
                'max_uses'     => 1000,
                'used_count'   => 0,
                'expires_at'   => now()->addYear(),
            ]
        );
    }
}
