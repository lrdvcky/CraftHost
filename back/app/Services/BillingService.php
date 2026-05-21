<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class BillingService
{
    public function charge(int $userId, float $amount, string $description = ''): bool
    {
        return DB::transaction(function () use ($userId, $amount) {
            $user = User::lockForUpdate()->find($userId);

            if ($user->balance < $amount) {
                throw new \Exception('Недостаточно средств на балансе');
            }

            $user->balance = round($user->balance - $amount, 2);
            $user->save();

            return true;
        });
    }

    public function refund(int $userId, float $amount): bool
    {
        return DB::transaction(function () use ($userId, $amount) {
            $user = User::lockForUpdate()->find($userId);
            $user->balance = round($user->balance + $amount, 2);
            $user->save();
            return true;
        });
    }
}