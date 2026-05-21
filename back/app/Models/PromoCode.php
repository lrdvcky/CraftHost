<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromoCode extends Model
{
    // В таблице promo_codes только created_at — отключаем updated_at.
    const UPDATED_AT = null;

    protected $fillable = [
        'code', 'discount_pct', 'max_uses', 'used_count', 'expires_at',
    ];

    protected $casts = [
        'expires_at'   => 'datetime',
        'discount_pct' => 'integer',
        'max_uses'     => 'integer',
        'used_count'   => 'integer',
    ];

    public function uses() { return $this->hasMany(PromoUse::class); }

    /**
     * Может ли промокод быть применён вообще (не учитывая конкретного юзера).
     */
    public function isUsable(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->max_uses > 0 && $this->used_count >= $this->max_uses) return false;
        return true;
    }

    /**
     * Может ли данный пользователь применить этот промокод.
     */
    public function isUsableBy(int $userId): bool
    {
        if (!$this->isUsable()) return false;
        return !PromoUse::where('promo_code_id', $this->id)
            ->where('user_id', $userId)
            ->exists();
    }
}
