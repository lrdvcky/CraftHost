<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tariff extends Model
{
    protected $fillable = [
        'name', 'ram_mb', 'cpu_percent', 'disk_mb', 'slots', 'price_day'
    ];

    protected $casts = [
        'price_day' => 'decimal:2',
    ];

    public function servers() { return $this->hasMany(Server::class); }
}