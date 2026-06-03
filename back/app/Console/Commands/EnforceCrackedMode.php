<?php

namespace App\Console\Commands;

use App\Models\Server;
use App\Services\PterodactylService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Гарантирует, что на всех активных серверах CraftHost
 * выставлен online-mode=false (доступ для нелицензионных игроков).
 * Запускается планировщиком и автоматически правит и рестартит
 * сервер, если кто-то поменял настройку.
 */
class EnforceCrackedMode extends Command
{
    protected $signature   = 'servers:enforce-cracked';
    protected $description = 'Force online-mode=false on all active servers';

    public function handle(PterodactylService $ptero): int
    {
        $servers = Server::where('status', 'active')
            ->whereNotNull('ptero_server_id')
            ->get();

        $this->info("Проверяем {$servers->count()} активных серверов...");

        $fixed = 0;
        foreach ($servers as $server) {
            try {
                $props = $ptero->readFile($server->ptero_server_id, '/server.properties');
                if ($props === null || $props === '') {
                    // server.properties ещё не создан (сервер не стартовал) — пропускаем
                    continue;
                }

                // Уже cracked? Пропускаем.
                if (preg_match('/^online-mode\s*=\s*false/mi', $props)) {
                    continue;
                }

                if (preg_match('/^online-mode\s*=/mi', $props)) {
                    $props = preg_replace('/^online-mode\s*=.*/mi', 'online-mode=false', $props);
                } else {
                    $props = rtrim($props) . "\nonline-mode=false\n";
                }

                $ptero->uploadFile($server->ptero_server_id, '/server.properties', $props);
                try { $ptero->sendPowerSignal($server->ptero_server_id, 'restart'); } catch (\Throwable $e) {}

                $fixed++;
                $this->info("  ✓ сервер #{$server->id}: online-mode=false");
                Log::info("EnforceCracked: server #{$server->id} switched to online-mode=false");
            } catch (\Throwable $e) {
                $this->warn("  ! сервер #{$server->id}: " . $e->getMessage());
            }
        }

        $this->info("Готово. Исправлено: {$fixed}.");
        return self::SUCCESS;
    }
}
