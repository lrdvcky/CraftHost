<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'type', 'description'];

    const CACHE_PREFIX = 'setting:';
    const CACHE_TTL    = 3600;

    /**
     * Типизированное чтение из БД с кэшем.
     *
     * @template T
     * @param  T|null  $default
     * @return T|string|int|bool|float|array|null
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(self::CACHE_PREFIX . $key, self::CACHE_TTL, function () use ($key, $default) {
            $row = static::find($key);
            if (!$row) return $default;
            return static::castValue($row->value, $row->type);
        });
    }

    public static function put(string $key, mixed $value, string $type = 'string', ?string $description = null): self
    {
        $stored = is_array($value) ? json_encode($value) : (string) $value;
        $row = static::updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'type' => $type, 'description' => $description]
        );
        Cache::forget(self::CACHE_PREFIX . $key);
        return $row;
    }

    private static function castValue(?string $raw, string $type): mixed
    {
        if ($raw === null) return null;
        return match ($type) {
            'int'   => (int) $raw,
            'float' => (float) $raw,
            'bool'  => filter_var($raw, FILTER_VALIDATE_BOOLEAN),
            'json'  => json_decode($raw, true),
            default => $raw,
        };
    }
}
