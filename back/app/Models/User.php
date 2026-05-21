<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'email', 'password', 'balance', 'role', 'referrer_id', 'email_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'password' => 'hashed',
        'balance' => 'decimal:2',
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['email_verified'];

    public function getEmailVerifiedAttribute(): bool
    {
        return !is_null($this->email_verified_at);
    }

    // Связи
    public function servers() { return $this->hasMany(Server::class); }
    public function orders() { return $this->hasMany(Order::class); }
    public function supportTickets() { return $this->hasMany(SupportTicket::class); }
    public function referralCode() { return $this->hasOne(ReferralCode::class); }
    public function referrer() { return $this->belongsTo(User::class, 'referrer_id'); }
    public function referrals() { return $this->hasMany(User::class, 'referrer_id'); }
}
