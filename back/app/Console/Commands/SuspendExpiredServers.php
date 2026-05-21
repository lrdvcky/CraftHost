<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Server;
use App\Services\PterodactylService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Приостанавливает серверы, у которых истёк срок аренды.
 *
 * Запускается по расписанию каждые 5 минут (см. Kernel::schedule).
 * Можно вызвать вручную: php artisan servers:suspend-expired
 */
class SuspendExpiredServers extends Command
{
    protected $signature   = 'servers:suspend-expired';
    protected $description = 'Suspend servers whose expires_at has passed';

    public function handle(PterodactylService $ptero): int
    {
        $servers = Server::where('status', 'active')
            ->where('expires_at', '<', now())
            ->get();

        if ($servers->isEmpty()) {
            $this->info('Нет просроченных серверов.');
            return 0;
        }

        $this->info("Найдено {$servers->count()} просроченных серверов.");

        foreach ($servers as $server) {
            try {
                // Приостановка в Pterodactyl
                if ($server->ptero_server_id) {
                    $ptero->suspendServer($server->ptero_server_id);
                }

                $server->update(['status' => 'suspended']);

                // Уведомление пользователю
                Notification::create([
                    'user_id'    => $server->user_id,
                    'type'       => 'server_suspended',
                    'data'       => [
                        'server_id' => $server->id,
                        'reason'    => 'expired',
                        'message'   => "Сервер #{$server->id} приостановлен — истёк срок аренды. Продлите подписку в личном кабинете.",
                    ],
                    'created_at' => now(),
                ]);

                Log::info("SuspendExpired: server #{$server->id} suspended (expires_at={$server->expires_at})");
                $this->line("  ✓ Сервер #{$server->id} приостановлен");
            } catch (\Throwable $e) {
                Log::error("SuspendExpired: ошибка при приостановке server #{$server->id}", [
                    'error' => $e->getMessage(),
                ]);
                $this->error("  ✗ Ошибка сервера #{$server->id}: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
