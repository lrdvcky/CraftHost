<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServerMod extends Model
{
    const CREATED_AT = 'uploaded_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'server_id', 'mod_id', 'filename', 'size_bytes',
        'status', 'error', 'installed_at',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'uploaded_at'  => 'datetime',
        'size_bytes'   => 'integer',
    ];

    public function server() { return $this->belongsTo(Server::class); }
    public function mod()    { return $this->belongsTo(Mod::class); }
}
