<?php

namespace SocialSync\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use JsonException;
use Throwable;

class PlatformCredential extends Model
{
    protected $table = 'larapost_platform_credentials';

    protected $fillable = [
        'platform',
        'credentials',
    ];

    public function getCredentialsAttribute(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = $this->decodeJsonString((string) $value);

        if ($decoded !== null) {
            return $decoded;
        }

        try {
            $decrypted = Crypt::decryptString((string) $value);

            return $this->decodeJsonString($decrypted);
        } catch (Throwable) {
            return null;
        }
    }

    public function setCredentialsAttribute(mixed $value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['credentials'] = null;

            return;
        }

        if (is_string($value)) {
            $decoded = $this->decodeJsonString($value);
            $value = $decoded ?? ['value' => $value];
        }

        if (!is_array($value)) {
            throw new \InvalidArgumentException('Credentials must be an array, JSON string, or null.');
        }

        try {
            $this->attributes['credentials'] = Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            throw new \InvalidArgumentException('Failed to encode credentials as JSON.', 0, $exception);
        }
    }

    public function scopePlatform($query, string $platform)
    {
        return $query->where('platform', strtolower($platform));
    }

    protected function decodeJsonString(string $value): ?array
    {
        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        return is_array($decoded) ? $decoded : null;
    }
}
