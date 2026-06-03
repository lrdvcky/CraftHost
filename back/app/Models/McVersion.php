<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class McVersion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'slug', 'label', 'type', 'jar_url', 'ptero_egg_id', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'ptero_egg_id' => 'integer',
        'sort_order'   => 'integer',
    ];

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeOrdered($q) { return $q->orderBy('sort_order')->orderBy('label'); }
}
