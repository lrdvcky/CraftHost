<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Server;
use App\Services\PterodactylService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Удаляет серверы, которые висят в suspended более 7 дней.
 *
 * Запускается по расписанию раз в сутки (см. Kernel::schedule).
 * Можно вызвать вручную: php artisan servers:delete-abandoned
 */
class DeleteAbandonedServers extends Command
{
    protected $signature   = 'servers:delete-abandoned {--days=7 : Через сколько дней после приостановки удалять}';
    protected $description = 'Delete servers that have been suspended for too long';

    public function handle(PterodactylService $ptero): int
    {
        $days = (int) $this->option('days');

        $servers = Server::where('status', 'suspended')
            ->where('expires_at', '<', now()->subDays($days))
            ->get();

        if ($servers->isEmpty()) {
            $this->info('Нет серверов для удаления.');
            return 0;
        }

        $this->info("Найдено {$servers->count()} серверов для удаления (suspended > {$days} дней).");

        foreach ($servers as $server) {
            try {
                // Удаление из Pterodactyl
                if ($server->ptero_server_id) {
                    try {
                        $ptero->deleteServer($server->ptero_server_id);
                    } catch (\Throwable $e) {
                        Log::warning("DeleteAbandoned: не удалось удалить из Pterodactyl server #{$server->id}", [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                $server->update(['status' => 'deleted']);

                Notification::create([
                    'user_id'    => $server->user_id,
                    'type'       => 'server_deleted',
                    'data'       => [
                        'server_id' => $server->id,
                        'message'   => "Сервер #{$server->id} удалён — аренда не была продлена в течение {$days} дней.",
                    ],
                    'created_at' => now(),
                ]);

                Log::info("DeleteAbandoned: server #{$server->id} deleted");
                $this->line("  ✓ Сервер #{$server->id} удалён");
            } catch (\Throwable $e) {
                Log::error("DeleteAbandoned: ошибка при удалении server #{$server->id}", [
                    'error' => $e->getMessage(),
                ]);
                $this->error("  ✗ Ошибка сервера #{$server->id}: {$e->getMessage()}");
            }
        }

        return 0;
    }
}
