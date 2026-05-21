<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    /**
     * Возможные значения status:
     *   pending       — заказ создан, Job ещё не стартовал
     *   provisioning  — Job выполняется, идёт создание в Pterodactyl
     *   active        — сервер создан и работает
     *   suspended     — приостановлен (истёк срок / админ заблокировал)
     *   deleted       — удалён
     *   error         — провизия завершилась ошибкой
     */
    protected $fillable = [
        'user_id',
        'tariff_id',
        'node_id',
        'ptero_server_id',
        'mc_version',
        'expires_at',
        'status',
        'server_ip',
        'server_port',
        'sftp_password',
        'provisioning_error',
    ];

    protected $casts = [
        'expires_at'  => 'datetime',
        'server_port' => 'integer',
    ];

    /**
     * Скрываем технические поля из JSON ответов API.
     */
    protected $hidden = [
        'sftp_password',
        'provisioning_error',
    ];

    public function user()    { return $this->belongsTo(User::class); }
    public function tariff()  { return $this->belongsTo(Tariff::class); }
    public function node()    { return $this->belongsTo(Node::class); }
    public function backups() { return $this->hasMany(Backup::class); }
    public function mods()    { return $this->hasMany(ServerMod::class); }

    public function getAddressAttribute(): ?string
    {
        if (!$this->server_ip || !$this->server_port) {
            return null;
        }
        return $this->server_ip . ':' . $this->server_port;
    }

    public function isProvisioned(): bool
    {
        return $this->status === 'active' && !empty($this->ptero_server_id);
    }

    // Scopes
    public function scopeActive($q)        { return $q->where('status', 'active'); }
    public function scopeUnfinished($q)    { return $q->whereIn('status', ['pending', 'provisioning']); }
}
