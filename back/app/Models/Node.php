<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Node extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'name', 'ptero_node_id', 'location', 'fqdn', 'max_servers', 'is_active',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'ptero_node_id' => 'integer',
        'max_servers'   => 'integer',
    ];

    public function servers() { return $this->hasMany(Server::class); }

    public function scopeActive($q) { return $q->where('is_active', true); }
}
