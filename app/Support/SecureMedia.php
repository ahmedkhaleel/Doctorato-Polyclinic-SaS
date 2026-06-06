<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generates short-lived, signed URLs for serving sensitive patient files (PHI)
 * through the authenticated MediaController instead of the world-readable
 * /storage/... path. An <img>/<a> using this URL still works (same-origin
 * cookie), but the file is no longer guessable/enumerable by anonymous users.
 *
 * Usage (server side, e.g. in a model accessor or controller payload):
 *   SecureMedia::url($model->image_path)            // private disk
 *   SecureMedia::url($path, 'public')               // legacy public-disk file
 */
class SecureMedia
{
    /** Default link lifetime — long enough for a page session, short enough to limit leakage. */
    public const TTL_MINUTES = 60;

    /** Private disk new PHI uploads land on. */
    public const PRIVATE_DISK = 'local';

    /** Legacy disk holding files not yet migrated. */
    public const PUBLIC_DISK = 'public';

    public static function url(?string $path, string $disk = self::PRIVATE_DISK, ?int $ttlMinutes = null): ?string
    {
        if (empty($path)) {
            return null;
        }

        return URL::temporarySignedRoute(
            'media.show',
            now()->addMinutes($ttlMinutes ?? self::TTL_MINUTES),
            ['disk' => $disk, 'path' => $path],
        );
    }

    /**
     * Transitional helpers — during the public→private migration a sensitive file
     * may live on EITHER disk. These check the private disk first, then fall back
     * to the legacy public disk, so reads/deletes work before and after migration.
     */
    public static function diskFor(?string $path): ?string
    {
        if (empty($path)) {
            return null;
        }
        if (Storage::disk(self::PRIVATE_DISK)->exists($path)) {
            return self::PRIVATE_DISK;
        }
        if (Storage::disk(self::PUBLIC_DISK)->exists($path)) {
            return self::PUBLIC_DISK;
        }

        return null;
    }

    public static function exists(?string $path): bool
    {
        return self::diskFor($path) !== null;
    }

    public static function delete(?string $path): bool
    {
        $disk = self::diskFor($path);
        if ($disk === null) {
            return false;
        }

        return Storage::disk($disk)->delete($path);
    }

    /** Absolute filesystem path on whichever disk holds the file (or null). */
    public static function path(?string $path): ?string
    {
        $disk = self::diskFor($path);

        return $disk ? Storage::disk($disk)->path($path) : null;
    }

    /** Streamed download response from whichever disk holds the file. */
    public static function download(?string $path, ?string $name = null): ?StreamedResponse
    {
        $disk = self::diskFor($path);
        if ($disk === null) {
            return null;
        }

        return Storage::disk($disk)->download($path, $name ?? basename($path));
    }
}
