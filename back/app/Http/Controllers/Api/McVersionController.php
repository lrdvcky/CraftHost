<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\McVersion;
use Illuminate\Http\Request;

class McVersionController extends Controller
{
    /** GET /api/mc-versions — публичный список активных версий для OrderView */
    public function index()
    {
        return response()->json(
            McVersion::active()->ordered()->get(['slug', 'label', 'type'])
        );
    }

    /** GET /api/admin/mc-versions */
    public function adminIndex()
    {
        return response()->json(McVersion::ordered()->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'slug'         => 'required|string|max:64|unique:mc_versions,slug',
            'label'        => 'required|string|max:128',
            'type'         => 'required|string|max:32',
            'jar_url'      => 'nullable|url|max:512',
            'ptero_egg_id' => 'nullable|integer',
            'is_active'    => 'boolean',
            'sort_order'   => 'integer',
        ]);

        $v = McVersion::create($data);
        AuditLog::record('mc_version.created', 'mc_version', $v->id, ['slug' => $v->slug]);
        return response()->json($v, 201);
    }

    public function update(Request $request, $id)
    {
        $v = McVersion::findOrFail($id);
        $data = $request->validate([
            'label'        => 'string|max:128',
            'type'         => 'string|max:32',
            'jar_url'      => 'nullable|url|max:512',
            'ptero_egg_id' => 'nullable|integer',
            'is_active'    => 'boolean',
            'sort_order'   => 'integer',
        ]);
        $v->update($data);
        AuditLog::record('mc_version.updated', 'mc_version', $v->id, $data);
        return response()->json($v);
    }

    public function destroy($id)
    {
        $v = McVersion::findOrFail($id);
        $v->delete();
        AuditLog::record('mc_version.deleted', 'mc_version', $id);
        return response()->json(['message' => 'Версия удалена']);
    }
}
