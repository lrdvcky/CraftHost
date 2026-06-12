<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tariff extends Model
{
    protected $fillable = [
        'name', 'tagline', 'description', 'features', 'image', 'is_popular',
        'ram_mb', 'cpu_percent', 'disk_mb', 'slots', 'price_day'
    ];

    protected $casts = [
        'price_day'  => 'decimal:2',
        'features'   => 'array',
        'is_popular' => 'boolean',
    ];

    public function servers() { return $this->hasMany(Server::class); }
}