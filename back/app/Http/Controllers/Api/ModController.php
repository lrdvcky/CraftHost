<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Mod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ModController extends Controller
{
    /**
     * Публичный каталог модов/плагинов с фильтрами.
     * GET /api/mods?kind=plugin&loader=paper&mc_version=1.20.4
     */
    public function index(Request $request)
    {
        $q = Mod::query()->where('is_active', true);

        if ($request->filled('kind')) {
            $q->where('kind', $request->kind);
        }
        if ($request->filled('loader')) {
            $q->where('loader', $request->loader);
        }

        $mods = $q->orderBy('name')->get();

        if ($request->filled('mc_version')) {
            $mcv = (string) $request->mc_version;
            $mods = $mods->filter(fn (Mod $m) => $m->supportsMcVersion($mcv))->values();
        }

        return response()->json($mods);
    }

    public function adminIndex()
    {
        return response()->json(
            Mod::withCount('installations')->orderBy('id', 'desc')->get()
        );
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:150',
            'kind'        => 'required|in:mod,plugin',
            'loader'      => 'required|in:forge,fabric,paper,spigot,bukkit',
            'mc_versions' => 'nullable|array',
            'mc_versions.*' => 'string|max:20',
            'description' => 'nullable|string|max:5000',
            'is_active'   => 'nullable|boolean',
            'file'        => 'required|file|mimetypes:application/java-archive,application/zip,application/octet-stream|max:204800',
            'icon'        => 'nullable|image|max:2048',
        ]);

        $this->validateLoaderKind($data['kind'], $data['loader']);

        $slug = $this->uniqueSlug($data['name']);

        $file = $request->file('file');
        $filename = $file->getClientOriginalName();
        $filePath = $file->storeAs('mods', $slug . '_' . Str::random(8) . '.jar', 'public');

        $iconPath = null;
        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('mod-icons', 'public');
        }

        $mod = Mod::create([
            'name'        => $data['name'],
            'slug'        => $slug,
            'kind'        => $data['kind'],
            'loader'      => $data['loader'],
            'mc_versions' => $data['mc_versions'] ?? null,
            'description' => $data['description'] ?? null,
            'file_path'   => $filePath,
            'filename'    => $filename,
            'size_bytes'  => $file->getSize(),
            'icon_path'   => $iconPath,
            'is_active'   => $data['is_active'] ?? true,
        ]);

        AuditLog::record('mod.created', 'mod', $mod->id, [
            'name'   => $mod->name,
            'kind'   => $mod->kind,
            'loader' => $mod->loader,
        ]);

        return response()->json($mod, 201);
    }

    public function update(Request $request, $id)
    {
        $mod = Mod::findOrFail($id);

        $data = $request->validate([
            'name'        => 'string|max:150',
            'kind'        => 'in:mod,plugin',
            'loader'      => 'in:forge,fabric,paper,spigot,bukkit',
            'mc_versions' => 'nullable|array',
            'mc_versions.*' => 'string|max:20',
            'description' => 'nullable|string|max:5000',
            'is_active'   => 'boolean',
            'file'        => 'nullable|file|mimetypes:application/java-archive,application/zip,application/octet-stream|max:204800',
            'icon'        => 'nullable|image|max:2048',
        ]);

        $kind   = $data['kind']   ?? $mod->kind;
        $loader = $data['loader'] ?? $mod->loader;
        $this->validateLoaderKind($kind, $loader);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            if ($mod->file_path) {
                Storage::disk('public')->delete($mod->file_path);
            }
            $data['filename']   = $file->getClientOriginalName();
            $data['file_path']  = $file->storeAs('mods', $mod->slug . '_' . Str::random(8) . '.jar', 'public');
            $data['size_bytes'] = $file->getSize();
        }

        if ($request->hasFile('icon')) {
            if ($mod->icon_path) {
                Storage::disk('public')->delete($mod->icon_path);
            }
            $data['icon_path'] = $request->file('icon')->store('mod-icons', 'public');
        }

        $mod->update($data);

        AuditLog::record('mod.updated', 'mod', $mod->id, ['fields' => array_keys($data)]);

        return response()->json($mod->fresh());
    }

    public function destroy($id)
    {
        $mod = Mod::findOrFail($id);

        $hasInstalls = $mod->installations()->whereIn('status', ['installed', 'installing'])->exists();
        if ($hasInstalls) {
            return response()->json([
                'error' => 'Нельзя удалить — мод установлен на одном или нескольких серверах.',
            ], 422);
        }

        if ($mod->file_path) {
            Storage::disk('public')->delete($mod->file_path);
        }
        if ($mod->icon_path) {
            Storage::disk('public')->delete($mod->icon_path);
        }

        $mod->delete();
        AuditLog::record('mod.deleted', 'mod', $id);

        return response()->json(['message' => 'Мод удалён']);
    }

    private function validateLoaderKind(string $kind, string $loader): void
    {
        $allowed = [
            'mod'    => ['forge', 'fabric'],
            'plugin' => ['paper', 'spigot', 'bukkit'],
        ];
        if (!in_array($loader, $allowed[$kind] ?? [], true)) {
            abort(422, "Для kind={$kind} допустимы loader: " . implode(', ', $allowed[$kind] ?? []));
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'mod';
        $slug = $base;
        $i = 1;
        while (Mod::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
