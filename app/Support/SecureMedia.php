<?php

namespace App\Support;

use Illuminate\Support\Facades\URL;

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

    public static function url(?string $path, string $disk = 'local', ?int $ttlMinutes = null): ?string
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
}
