<?php

namespace SocialSync\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialAccount extends Model
{
    protected $fillable = [
        'platform',
        'account_name',
        'account_username',
        'account_id_on_platform',
        'credentials',
        'is_active',
        'last_used_at',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function scheduledPosts(): HasMany
    {
        return $this->hasMany(ScheduledPost::class, 'account_id');
    }

    public function getCredentialsAttribute($value)
    {
        return $value ? json_decode(decrypt($value), true) : null;
    }

    public function setCredentialsAttribute($value)
    {
        $this->attributes['credentials'] = encrypt(json_encode($value));
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
