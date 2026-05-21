<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PromoCode;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    /** GET /api/admin/promo */
    public function index()
    {
        return response()->json(PromoCode::withCount('uses')->latest()->get());
    }

    /** POST /api/admin/promo */
    public function store(Request $request)
    {
        $data = $request->validate([
            'code'         => 'required|string|max:32|unique:promo_codes,code',
            'discount_pct' => 'required|integer|min:1|max:100',
            'max_uses'     => 'integer|min:0',
            'expires_at'   => 'nullable|date',
        ]);

        $promo = PromoCode::create($data + ['used_count' => 0]);
        AuditLog::record('promo.created', 'promo_code', $promo->id, ['code' => $promo->code]);
        return response()->json($promo, 201);
    }

    /** PUT /api/admin/promo/{id} */
    public function update(Request $request, $id)
    {
        $promo = PromoCode::findOrFail($id);
        $data = $request->validate([
            'discount_pct' => 'integer|min:1|max:100',
            'max_uses'     => 'integer|min:0',
            'expires_at'   => 'nullable|date',
        ]);
        $promo->update($data);
        AuditLog::record('promo.updated', 'promo_code', $promo->id, $data);
        return response()->json($promo);
    }

    /** DELETE /api/admin/promo/{id} */
    public function destroy($id)
    {
        $promo = PromoCode::findOrFail($id);
        $promo->delete();
        AuditLog::record('promo.deleted', 'promo_code', $id);
        return response()->json(['message' => 'Промокод удалён']);
    }
}
