<?php
namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['key' => 'maintenance_mode',      'value' => '0',                          'type' => 'bool',   'description' => 'Отключить приём новых заказов'],
            ['key' => 'maintenance_message',   'value' => 'Технические работы. Заказы временно недоступны.', 'type' => 'string', 'description' => 'Сообщение при включённом maintenance_mode'],
            ['key' => 'max_servers_per_user',  'value' => '5',                          'type' => 'int',    'description' => 'Максимум серверов на одного пользователя (0 = безлимит)'],
            ['key' => 'min_topup_amount',      'value' => '50',                         'type' => 'int',    'description' => 'Минимальная сумма пополнения, ₽'],
            ['key' => 'support_email',         'value' => 'support@crafthost.ru',       'type' => 'string', 'description' => 'Контактный email поддержки'],
        ];

        foreach ($rows as $row) {
            Setting::updateOrCreate(['key' => $row['key']], $row);
        }
    }
}
