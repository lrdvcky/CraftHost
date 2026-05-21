<?php
namespace Database\Seeders;

use App\Models\Node;
use Illuminate\Database\Seeder;

class NodeSeeder extends Seeder
{
    public function run(): void
    {
        Node::updateOrCreate(
            ['name' => 'Default Node'],
            [
                'ptero_node_id' => 1,
                'location'      => 'ru-mow1',
                'fqdn'          => 'node1.crafthost.local',
                'max_servers'   => 0, // безлимит
                'is_active'     => true,
                'created_at'    => now(),
            ]
        );
    }
}
