<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\PterodactylService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index(Request $request, $serverId, PterodactylService $ptero)
    {
        $server = $request->user()->servers()->findOrFail($serverId);
        $backups = $server->backups()->latest()->get();

        // Подтягиваем актуальные размеры из Pterodactyl для бэкапов,
        // у которых size_bytes ещё нулевой (создание было асинхронным).
        if ($server->ptero_server_id && $backups->where('size_bytes', 0)->isNotEmpty()) {
            try {
                $pteroBackups = $ptero->listBackups($server->ptero_server_id);
                foreach ($backups as $b) {
                    if ((int) $b->size_bytes !== 0 || !$b->ptero_backup_id) {
                        continue;
                    }
                    $a = $pteroBackups[$b->ptero_backup_id] ?? null;
                    if ($a && !empty($a['bytes']) && ($a['is_successful'] ?? true)) {
                        $b->update(['size_bytes' => (int) $a['bytes']]);
                    }
                }
            } catch (\Throwable $e) {
                // не валим листинг из-за ошибки опроса
            }
        }

        return response()->json($backups->fresh());
    }

    public function store(Request $request, $serverId, PterodactylService $ptero)
    {
        $server = $request->user()->servers()->findOrFail($serverId);

        if (!$server->ptero_server_id || $server->status !== 'active') {
            return response()->json([
                'error' => 'Бэкап можно создать только для активного сервера.',
            ], 422);
        }

        try {
            $pteroBackup = $ptero->createBackup($server->ptero_server_id);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Pterodactyl: ' . $e->getMessage()], 502);
        }

        // Pterodactyl возвращает идентификатор как 'uuid'; на всякий случай
        // поддерживаем оба ключа (uuid / id).
        $pteroId = $pteroBackup['uuid'] ?? $pteroBackup['id'] ?? null;

        $backup = Backup::create([
            'server_id'       => $server->id,
            'ptero_backup_id' => $pteroId ?? ('backup-' . uniqid()),
            'size_bytes'      => $pteroBackup['bytes'] ?? 0,
        ]);

        return response()->json($backup, 201);
    }

    /**
     * Восстановить бэкап.
     * POST /api/servers/{serverId}/backups/{backupId}/restore
     */
    public function restore(Request $request, $serverId, $backupId, PterodactylService $ptero)
    {
        $server = $request->user()->servers()->findOrFail($serverId);
        $backup = $server->backups()->findOrFail($backupId);

        if (!$server->ptero_server_id || $server->status !== 'active') {
            return response()->json([
                'error' => 'Восстановление доступно только для активного сервера.',
            ], 422);
        }

        if (!$backup->ptero_backup_id) {
            return response()->json(['error' => 'У бэкапа нет привязки к Pterodactyl.'], 422);
        }

        try {
            $ptero->restoreBackup($server->ptero_server_id, $backup->ptero_backup_id);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Pterodactyl: ' . $e->getMessage()], 502);
        }

        return response()->json(['message' => 'Бэкап восстанавливается. Сервер будет перезагружен.']);
    }

    /**
     * Получить signed-ссылку на скачивание бэкапа.
     * GET /api/servers/{serverId}/backups/{backupId}/download
     */
    public function download(Request $request, $serverId, $backupId, PterodactylService $ptero)
    {
        $server = $request->user()->servers()->findOrFail($serverId);
        $backup = $server->backups()->findOrFail($backupId);

        if (!$server->ptero_server_id || !$backup->ptero_backup_id) {
            return response()->json(['error' => 'У бэкапа нет привязки к Pterodactyl.'], 422);
        }

        try {
            $url = $ptero->getBackupDownloadUrl($server->ptero_server_id, $backup->ptero_backup_id);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Pterodactyl: ' . $e->getMessage()], 502);
        }

        if (!$url) {
            return response()->json(['error' => 'Не удалось получить ссылку на скачивание.'], 502);
        }

        return response()->json(['url' => $url]);
    }
}
