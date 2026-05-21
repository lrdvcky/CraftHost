<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    // Отключаем обновление колонки updated_at
    const UPDATED_AT = null; 

    protected $fillable = [
        'user_id', 'amount', 'provider', 'external_id', 'confirmation_url', 'status', 'meta'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'meta'   => 'array',
    ];

    public function user() { return $this->belongsTo(User::class); }
}