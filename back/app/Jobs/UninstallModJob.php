<?php

namespace App\Jobs;

use App\Models\ServerMod;
use App\Services\PterodactylService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class UninstallModJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $backoff = 15;

    public function __construct(public ServerMod $install) {}

    public function handle(PterodactylService $ptero): void
    {
        $install = $this->install->fresh(['mod', 'server']);
        if (!$install) {
            return;
        }

        $server = $install->server;
        $mod    = $install->mod;

        if ($server && $server->ptero_server_id && $mod) {
            $dir = ltrim($mod->targetDir(), '/');
            try {
                $ptero->deleteFiles($server->ptero_server_id, '/' . $dir, [$install->filename]);
            } catch (Throwable $e) {
                Log::warning('UninstallModJob: deleteFiles failed', ['err' => $e->getMessage()]);
            }
            try {
                $ptero->sendPowerSignal($server->ptero_server_id, 'restart');
            } catch (Throwable $e) {}
        }

        $install->delete();
    }

    public function failed(Throwable $e): void
    {
        $this->install->refresh()?->update([
            'status' => 'failed',
            'error'  => mb_substr($e->getMessage(), 0, 250),
        ]);
    }
}
