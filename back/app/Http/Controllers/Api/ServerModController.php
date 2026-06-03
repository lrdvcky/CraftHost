<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\InstallModJob;
use App\Jobs\UninstallModJob;
use App\Models\Mod;
use App\Models\Server;
use App\Models\ServerMod;
use Illuminate\Http\Request;

class ServerModController extends Controller
{
    /**
     * GET /api/servers/{id}/mods
     * Возвращает установленные моды + каталог совместимых.
     */
    public function index(Request $request, $id)
    {
        $server = $this->findServer($request, $id);

        $installed = $server->mods()->with('mod')->orderByDesc('id')->get();

        $catalog = Mod::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->filter(fn (Mod $m) => $m->supportsLoader($server->mc_version) && $m->supportsMcVersion($server->mc_version))
            ->values();

        return response()->json([
            'installed' => $installed,
            'catalog'   => $catalog,
        ]);
    }

    /**
     * POST /api/servers/{id}/mods  { mod_id }
     * Ставит мод в очередь установки.
     */
    public function store(Request $request, $id)
    {
        $request->validate(['mod_id' => 'required|integer|exists:mods,id']);

        $server = $this->findServer($request, $id);

        if ($server->status !== 'active') {
            return response()->json(['error' => 'Установка возможна только на активном сервере.'], 409);
        }
        if (!$server->ptero_server_id) {
            return response()->json(['error' => 'У сервера не привязан Pterodactyl ID.'], 422);
        }

        $mod = Mod::where('is_active', true)->findOrFail($request->mod_id);

        if (!$mod->supportsLoader($server->mc_version)) {
            return response()->json(['error' => 'Этот ' . ($mod->kind === 'plugin' ? 'плагин' : 'мод') . ' несовместим с ядром сервера.'], 422);
        }
        if (!$mod->supportsMcVersion($server->mc_version)) {
            return response()->json(['error' => 'Этот ' . ($mod->kind === 'plugin' ? 'плагин' : 'мод') . ' несовместим с версией Minecraft сервера.'], 422);
        }

        $exists = ServerMod::where('server_id', $server->id)
            ->where('mod_id', $mod->id)
            ->whereIn('status', ['installing', 'installed'])
            ->exists();
        if ($exists) {
            return response()->json(['error' => 'Этот мод уже установлен или ставится.'], 409);
        }

        $install = ServerMod::create([
            'server_id'   => $server->id,
            'mod_id'      => $mod->id,
            'filename'    => $mod->filename,
            'size_bytes'  => $mod->size_bytes,
            'status'      => 'installing',
            'uploaded_at' => now(),
        ]);

        InstallModJob::dispatch($install);

        return response()->json($install->load('mod'), 202);
    }

    /**
     * DELETE /api/servers/{id}/mods/{installId}
     */
    public function destroy(Request $request, $id, $installId)
    {
        $server = $this->findServer($request, $id);
        $install = $server->mods()->findOrFail($installId);

        if ($install->status === 'removing') {
            return response()->json(['error' => 'Удаление уже выполняется.'], 409);
        }

        $install->update(['status' => 'removing']);
        UninstallModJob::dispatch($install);

        return response()->json(['message' => 'Удаление поставлено в очередь.'], 202);
    }

    private function findServer(Request $request, $id): Server
    {
        return $request->user()->servers()->findOrFail($id);
    }
}
