<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Mod extends Model
{
    protected $fillable = [
        'name', 'slug', 'kind', 'loader', 'mc_versions',
        'description', 'file_path', 'filename', 'size_bytes',
        'icon_path', 'is_active',
    ];

    protected $casts = [
        'mc_versions' => 'array',
        'is_active'   => 'boolean',
        'size_bytes'  => 'integer',
    ];

    protected $appends = ['file_url', 'icon_url'];

    public function installations()
    {
        return $this->hasMany(ServerMod::class);
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    public function getIconUrlAttribute(): ?string
    {
        return $this->icon_path ? Storage::disk('public')->url($this->icon_path) : null;
    }

    public function targetDir(): string
    {
        return $this->kind === 'plugin' ? '/plugins' : '/mods';
    }

    public function supportsMcVersion(string $mcVersion): bool
    {
        if (empty($this->mc_versions)) {
            return true;
        }
        $clean = preg_replace('/^(vanilla|paper|forge|fabric|sponge)_/', '', $mcVersion);
        return in_array($clean, $this->mc_versions, true) || in_array($mcVersion, $this->mc_versions, true);
    }

    public function supportsLoader(string $serverMcVersion): bool
    {
        if (!preg_match('/^(vanilla|paper|forge|fabric|sponge)_/', $serverMcVersion, $m)) {
            return true;
        }
        $serverLoader = $m[1];
        return match ($this->loader) {
            'forge'  => $serverLoader === 'forge',
            'fabric' => $serverLoader === 'fabric',
            'paper'  => in_array($serverLoader, ['paper'], true),
            'spigot' => in_array($serverLoader, ['paper', 'sponge'], true),
            'bukkit' => in_array($serverLoader, ['paper', 'sponge'], true),
            default  => false,
        };
    }
}
