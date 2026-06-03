<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Backup extends Model
{
    const UPDATED_AT = null; // В таблице только created_at

    protected $fillable = [
        'server_id', 'ptero_backup_id', 'size_bytes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function server() { return $this->belongsTo(Server::class); }
}
