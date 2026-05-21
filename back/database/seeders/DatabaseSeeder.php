<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Tariff::insert([
            ['name' => 'Dirt',    'ram_mb' => 1024, 'cpu_percent' => 100, 'disk_mb' => 10240, 'slots' => 10, 'price_day' => 10.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Iron',    'ram_mb' => 2048, 'cpu_percent' => 150, 'disk_mb' => 20480, 'slots' => 20, 'price_day' => 20.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Diamond', 'ram_mb' => 4096, 'cpu_percent' => 200, 'disk_mb' => 40960, 'slots' => 50, 'price_day' => 40.00, 'created_at' => now(), 'updated_at' => now()],
        ]);

        User::create([
            'email'    => 'admin@crafthost.ru',
            'password' => Hash::make('password'),
            'balance'  => 0,
            'role'     => 'admin',
        ]);

        $this->call([
            McVersionSeeder::class,
            NodeSeeder::class,
            SettingSeeder::class,
            PromoCodeSeeder::class,
        ]);

        echo "Тарифы созданы\n";
        echo "Администратор создан: admin@crafthost.ru / password\n";
    }
}
