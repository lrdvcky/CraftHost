<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Backup;
use App\Services\PterodactylService;
use Illuminate\Http\Request;

class BackupController extends Controller
{
    public function index(Request $request, $serverId)
    {
        $server = $request->user()->servers()->findOrFail($serverId);
        return response()->json($server->backups()->latest()->get());
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
}
