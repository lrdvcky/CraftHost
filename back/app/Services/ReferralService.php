<?php

namespace App\Services;

use App\Models\User;
use App\Models\ReferralCommission;

class ReferralService
{
    const COMMISSION_PERCENT = 10;

    public function processCommission(int $referredUserId, float $paymentAmount): void
    {
        $referred = User::find($referredUserId);

        // Если нет реферера - ничего не делаем
        if (!$referred || !$referred->referrer_id) return;

        // Комиссия только за ПЕРВУЮ оплату
        $alreadyPaid = ReferralCommission::where('referred_id', $referredUserId)->exists();
        if ($alreadyPaid) return;

        $commissionAmount = round($paymentAmount * self::COMMISSION_PERCENT / 100, 2);

        // Начисляем реферерру
        $referrer = User::find($referred->referrer_id);
        if (!$referrer) return;

        $referrer->balance += $commissionAmount;
        $referrer->save();

        ReferralCommission::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $referredUserId,
            'amount' => $commissionAmount,
        ]);
    }
}