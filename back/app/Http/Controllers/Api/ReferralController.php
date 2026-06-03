<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ReferralCode;
use App\Models\PromoCode;
use App\Models\ReferralCommission;
use Illuminate\Support\Str;

class ReferralController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $code = $user->referralCode;

        $stats = [
            'code' => $code ? $code->code : null,
            'referrals_count' => $user->referrals()->count(),
            'total_earned' => ReferralCommission::where('referrer_id', $user->id)->sum('amount'),
            'commissions' => ReferralCommission::where('referrer_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->limit(20)
                ->get(['id', 'amount', 'created_at']),
        ];

        return response()->json($stats);
    }

    public function generate(Request $request)
    {
        $user = $request->user();
        if ($user->referralCode) {
            return response()->json(['error' => 'Код уже существует'], 400);
        }

        $codeStr = Str::upper(Str::random(8));

        $code = ReferralCode::create([
            'user_id' => $user->id,
            'code' => $codeStr,
        ]);

        // Также создаём промокод на 3% скидку с тем же кодом
        if (!PromoCode::where('code', $codeStr)->exists()) {
            PromoCode::create([
                'code'         => $codeStr,
                'discount_pct' => 3,
                'max_uses'     => 0, // безлимит
                'used_count'   => 0,
                'expires_at'   => null,
            ]);
        }

        return response()->json($code);
    }
}