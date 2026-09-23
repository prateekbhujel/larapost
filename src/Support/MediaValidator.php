<?php

namespace SocialSync\Support;

use SocialSync\Exceptions\SocialSyncException;

final class MediaValidator
{
    public static function validate(string $path, string $type): void
    {
        if (!in_array($type, ['image', 'video'], true)) {
            throw new SocialSyncException(sprintf('Unsupported media type "%s".', $type));
        }

        if (!is_file($path)) {
            return;
        }

        if (!is_readable($path)) {
            throw new SocialSyncException(sprintf('Media file "%s" is not readable.', $path));
        }

        $size = filesize($path);
        $maxBytes = (int) config(
            $type === 'image' ? 'larapost.media.max_image_size' : 'larapost.media.max_video_size',
            $type === 'image' ? 5 * 1024 * 1024 : 100 * 1024 * 1024
        );

        if ($size !== false && $maxBytes > 0 && $size > $maxBytes) {
            throw new SocialSyncException(sprintf(
                '%s media exceeds the configured maximum size of %d bytes.',
                ucfirst($type),
                $maxBytes
            ));
        }

        $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
        $allowed = (array) config(
            $type === 'image' ? 'larapost.media.allowed_image_types' : 'larapost.media.allowed_video_types',
            $type === 'image' ? ['jpg', 'jpeg', 'png', 'gif', 'webp'] : ['mp4', 'mov', 'avi', 'webm']
        );
        $allowed = array_map(static fn ($value): string => strtolower((string) $value), $allowed);

        if ($extension !== '' && $allowed !== [] && !in_array($extension, $allowed, true)) {
            throw new SocialSyncException(sprintf(
                'Unsupported %s file extension "%s".',
                $type,
                $extension
            ));
        }
    }
}
