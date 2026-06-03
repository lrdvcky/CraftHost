<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    const UPDATED_AT = null;
    protected $table = 'audit_log';

    protected $fillable = [
        'admin_id', 'action', 'target_type', 'target_id', 'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }

    /**
     * Удобный фасад для записи в журнал.
     *
     *   AuditLog::record('updated_user', 'user', $userId, ['old' => ..., 'new' => ...]);
     */
    public static function record(string $action, ?string $targetType = null, ?int $targetId = null, array $meta = []): self
    {
        return static::create([
            'admin_id'    => auth()->id(),
            'action'      => $action,
            'target_type' => $targetType,
            'target_id'   => $targetId,
            'meta'        => $meta ?: null,
        ]);
    }
}
