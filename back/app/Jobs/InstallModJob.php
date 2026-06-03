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
use Illuminate\Support\Facades\Storage;
use Throwable;

class InstallModJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $backoff = 20;

    public function __construct(public ServerMod $install) {}

    public function handle(PterodactylService $ptero): void
    {
        $install = $this->install->fresh(['mod', 'server']);
        if (!$install || !$install->mod || !$install->server) {
            return;
        }

        $server = $install->server;
        $mod    = $install->mod;

        if (!$server->ptero_server_id) {
            throw new \RuntimeException('У сервера нет ptero_server_id');
        }

        $dir = $mod->targetDir();
        $ptero->ensureDirectory($server->ptero_server_id, '/', ltrim($dir, '/'));

        $contents = Storage::disk('public')->get($mod->file_path);
        if ($contents === null) {
            throw new \RuntimeException('Файл мода не найден в storage: ' . $mod->file_path);
        }

        $remotePath = rtrim($dir, '/') . '/' . $install->filename;
        $ptero->uploadFile($server->ptero_server_id, $remotePath, $contents);

        $install->update([
            'status'       => 'installed',
            'installed_at' => now(),
            'error'        => null,
        ]);

        try {
            $ptero->sendPowerSignal($server->ptero_server_id, 'restart');
        } catch (Throwable $e) {
            Log::warning('InstallModJob: restart не удался', ['err' => $e->getMessage()]);
        }
    }

    public function failed(Throwable $e): void
    {
        $this->install->refresh()->update([
            'status' => 'failed',
            'error'  => mb_substr($e->getMessage(), 0, 250),
        ]);
    }
}
