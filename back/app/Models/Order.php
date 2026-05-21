<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    // Отключаем обновление колонки updated_at
    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'server_id', 'amount', 'type', 'status'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function server() { return $this->belongsTo(Server::class); }
}