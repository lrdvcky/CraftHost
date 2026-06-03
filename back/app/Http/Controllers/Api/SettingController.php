<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * GET /api/public-settings — публичные ключи, нужные фронту.
     * Возвращаем только те ключи, которые безопасно показать без авторизации.
     */
    public function publicIndex()
    {
        return response()->json([
            'maintenance_mode'    => Setting::get('maintenance_mode', false),
            'maintenance_message' => Setting::get('maintenance_message', ''),
            'min_topup_amount'    => Setting::get('min_topup_amount', 50),
            'support_email'       => Setting::get('support_email', ''),
        ]);
    }

    /** GET /api/admin/settings */
    public function adminIndex()
    {
        return response()->json(Setting::orderBy('key')->get());
    }

    /** PUT /api/admin/settings/{key} */
    public function update(Request $request, string $key)
    {
        $row = Setting::findOrFail($key);
        $data = $request->validate([
            'value' => 'nullable|string',
        ]);

        $old = $row->value;
        Setting::put($row->key, $data['value'] ?? '', $row->type, $row->description);

        AuditLog::record('setting.updated', 'setting', null, [
            'key' => $row->key,
            'old' => $old,
            'new' => $data['value'] ?? '',
        ]);

        return response()->json(Setting::find($row->key));
    }
}
