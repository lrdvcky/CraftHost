<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerMod extends Model
{
    const CREATED_AT = 'uploaded_at'; // Кастомное имя поля
    const UPDATED_AT = null;

    protected $fillable = [
        'server_id', 'filename', 'size_bytes'
    ];

    public function server() { return $this->belongsTo(Server::class); }
}