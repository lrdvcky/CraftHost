<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralCommission extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'referrer_id', 'referred_id', 'amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function referrer() { return $this->belongsTo(User::class, 'referrer_id'); }
    public function referredUser() { return $this->belongsTo(User::class, 'referred_id'); }
}