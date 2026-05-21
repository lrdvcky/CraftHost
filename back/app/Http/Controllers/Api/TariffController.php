<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tariff;
use Illuminate\Http\Request;

class TariffController extends Controller
{
    public function index()
    {
        return response()->json(Tariff::all());
    }

    public function show($id)
    {
        return response()->json(Tariff::findOrFail($id));
    }

    public function adminIndex()
    {
        return response()->json(Tariff::withCount('servers')->orderBy('id')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100',
            'ram_mb'      => 'required|integer|min:128',
            'cpu_percent' => 'required|integer|min:10',
            'disk_mb'     => 'required|integer|min:512',
            'slots'       => 'required|integer|min:1',
            'price_day'   => 'required|numeric|min:0',
        ]);

        $tariff = Tariff::create($data);
        AuditLog::record('tariff.created', 'tariff', $tariff->id, ['name' => $tariff->name]);
        return response()->json($tariff, 201);
    }

    public function update(Request $request, $id)
    {
        $tariff = Tariff::findOrFail($id);
        $old = $tariff->only(['name', 'ram_mb', 'cpu_percent', 'disk_mb', 'slots', 'price_day']);

        $data = $request->validate([
            'name'        => 'string|max:100',
            'ram_mb'      => 'integer|min:128',
            'cpu_percent' => 'integer|min:10',
            'disk_mb'     => 'integer|min:512',
            'slots'       => 'integer|min:1',
            'price_day'   => 'numeric|min:0',
        ]);
        $tariff->update($data);

        AuditLog::record('tariff.updated', 'tariff', $tariff->id, ['old' => $old, 'new' => $data]);
        return response()->json($tariff);
    }

    public function destroy($id)
    {
        $tariff = Tariff::findOrFail($id);
        if ($tariff->servers()->whereNotIn('status', ['deleted'])->count() > 0) {
            return response()->json(['error' => 'Нельзя удалить тариф — на нём есть активные серверы.'], 422);
        }
        $tariff->delete();
        AuditLog::record('tariff.deleted', 'tariff', $id);
        return response()->json(['message' => 'Тариф удалён']);
    }
}
