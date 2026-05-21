<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Server;
use App\Services\PterodactylService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Асинхронная провизия сервера в Pterodactyl.
 */
class ProvisionServer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 30;

    public function __construct(public Server $server)
    {
        $this->onQueue('provisioning');
    }

    public function handle(PterodactylService $pterodactyl): void
    {
        $this->server->refresh();

        if (in_array($this->server->status, ['deleted', 'suspended'], true)) {
            Log::info("ProvisionServer: server #{$this->server->id} status='{$this->server->status}', пропускаем.");
            return;
        }

        $this->server->update(['status' => 'provisioning']);

        $result = $pterodactyl->createServer($this->server);

        // Автоматически принимаем EULA для нового сервера
        $pterodactyl->acceptEula($result['identifier']);

        $this->server->update([
            'ptero_server_id'    => $result['identifier'],
            'server_ip'          => $result['ip'],
            'server_port'        => $result['port'],
            'sftp_password'      => $result['sftp_password'],
            'node_id'            => $result['node_id'] ?? $this->server->node_id,
            'status'             => 'active',
            'provisioning_error' => null,
        ]);

        // Создаём уведомление для юзера.
        Notification::create([
            'user_id'    => $this->server->user_id,
            'type'       => 'server_ready',
            'data'       => [
                'server_id' => $this->server->id,
                'address'   => $result['ip'] . ':' . $result['port'],
                'tariff'    => $this->server->tariff?->name,
            ],
            'created_at' => now(),
        ]);

        Log::info("ProvisionServer: server #{$this->server->id} успешно создан", [
            'ptero_id' => $result['identifier'],
            'address'  => $result['ip'] . ':' . $result['port'],
            'stub'     => $pterodactyl->isStub(),
        ]);
    }

    public function failed(Throwable $e): void
    {
        Log::error("ProvisionServer: окончательный провал для server #{$this->server->id}", [
            'error' => $e->getMessage(),
        ]);

        $this->server->refresh()->update([
            'status'             => 'error',
            'provisioning_error' => mb_substr($e->getMessage(), 0, 1000),
        ]);

        Notification::create([
            'user_id'    => $this->server->user_id,
            'type'       => 'server_error',
            'data'       => [
                'server_id' => $this->server->id,
                'error'     => mb_substr($e->getMessage(), 0, 500),
            ],
            'created_at' => now(),
        ]);
    }
}
