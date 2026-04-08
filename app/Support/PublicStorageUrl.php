<?php

namespace App\Support;

/**
 * Root-relative URLs for files on the public disk. Avoids 404s when APP_URL (e.g. http://localhost)
 * does not match the origin users actually use (e.g. http://127.0.0.1:8000 with artisan serve).
 */
final class PublicStorageUrl
{
    public static function forPath(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        $normalized = str_replace('\\', '/', $path);

        return '/storage/'.ltrim($normalized, '/');
    }
}
