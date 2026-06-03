<?php
namespace Database\Seeders;

use App\Models\McVersion;
use Illuminate\Database\Seeder;

class McVersionSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => '1.20.4',        'label' => 'Vanilla 1.20.4', 'type' => 'vanilla', 'sort_order' => 10, 'ptero_egg_id' => 1],
            ['slug' => 'paper_1.20.4',  'label' => 'Paper 1.20.4',   'type' => 'paper',   'sort_order' => 20, 'ptero_egg_id' => 3],
            ['slug' => 'forge_1.20.1',  'label' => 'Forge 1.20.1',   'type' => 'forge',   'sort_order' => 30, 'ptero_egg_id' => 5],
            ['slug' => 'fabric_1.20.4', 'label' => 'Fabric 1.20.4',  'type' => 'fabric',  'sort_order' => 40, 'ptero_egg_id' => 7],
        ];

        foreach ($rows as $row) {
            McVersion::updateOrCreate(['slug' => $row['slug']], $row + ['is_active' => true]);
        }
    }
}
