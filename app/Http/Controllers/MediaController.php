<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Authenticated, signed serving of sensitive patient files (PHI). Replaces the
 * world-readable /storage/... path for clinical media.
 *
 * Defense in depth:
 *  - `signed` middleware: the URL must carry a valid server-issued signature,
 *    so anonymous users can't guess/enumerate file paths.
 *  - authenticated session required: even a leaked signed URL needs a logged-in
 *    session to resolve (so it can't be opened from a copied link in a browser
 *    that isn't signed into the clinic).
 *
 * Allowed disks are whitelisted; path traversal is rejected.
 */
class MediaController extends Controller
{
    /** Only these disks may be served through here. */
    private const ALLOWED_DISKS = ['local', 'public'];

    public function show(Request $request): StreamedResponse
    {
        if (! Auth::check()) {
            abort(403);
        }

        $disk = (string) $request->query('disk', 'local');
        $path = (string) $request->query('path', '');

        if (! in_array($disk, self::ALLOWED_DISKS, true)) {
            abort(404);
        }

        // Reject traversal / absolute paths.
        if ($path === '' || str_contains($path, '..') || str_starts_with($path, '/')) {
            abort(404);
        }

        $storage = Storage::disk($disk);
        if (! $storage->exists($path)) {
            // Transitional fallback: a file recorded on the private disk may
            // still physically live on the public disk (pre-migration).
            if ($disk === 'local' && Storage::disk('public')->exists($path)) {
                $storage = Storage::disk('public');
            } else {
                abort(404);
            }
        }

        // Inline so images render in <img>; the filename is preserved.
        return $storage->response($path, basename($path), [
            'Cache-Control' => 'private, max-age=300, no-store',
        ]);
    }
}
