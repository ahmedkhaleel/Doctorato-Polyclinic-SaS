<?php

namespace App\Console\Commands;

use App\Support\SecureMedia;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * S1 — moves sensitive patient files (PHI) from the world-readable `public`
 * disk to the private `local` disk. New uploads already land on `local`; this
 * relocates the historical files so they're no longer reachable at a guessable
 * /storage/... URL. Idempotent: a file already on `local` is skipped.
 *
 * Serving keeps working throughout — MediaController + SecureMedia fall back to
 * the public disk for any not-yet-migrated file, so display never breaks
 * mid-migration.
 */
class MigratePhiMedia extends Command
{
    protected $signature = 'media:migrate-phi
        {--dry-run : List what would move without copying or deleting}
        {--keep-public : Copy to private but do NOT delete the public original}';

    protected $description = 'Move sensitive patient files (PHI) from the public disk to the private disk';

    /** Path prefixes that hold PHI (must match the upload sites switched to the local disk). */
    private const PREFIXES = [
        'dental-xrays',
        'dental-comparisons',
        'uploads/dental/entries',
        'derma/photos',
        'cosmetic/photos',
        'cosmetic/signatures',
        'visit-photos',
        'patient-documents',
        'consents',
        'insurance-cards',
        // S1 follow-up — patient profile photos (PII avatars) + chat attachments.
        'uploads/patients',
        'patient-photos',
        'uploads/messages',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $keepPublic = (bool) $this->option('keep-public');

        $public = Storage::disk(SecureMedia::PUBLIC_DISK);
        $private = Storage::disk(SecureMedia::PRIVATE_DISK);

        $moved = 0;
        $skipped = 0;
        $missing = 0;
        $failed = 0;

        foreach (self::PREFIXES as $prefix) {
            if (! $public->exists($prefix)) {
                $this->line("· <comment>{$prefix}</comment> — nothing on public disk");

                continue;
            }

            $files = $public->allFiles($prefix);
            $this->line("· <info>{$prefix}</info> — ".count($files).' file(s) on public disk');

            foreach ($files as $path) {
                if ($private->exists($path)) {
                    $skipped++;

                    continue;
                }

                if ($dryRun) {
                    $this->line("    would move: {$path}");
                    $moved++;

                    continue;
                }

                $stream = $public->readStream($path);
                if ($stream === null) {
                    $this->error("    could not read: {$path}");
                    $missing++;

                    continue;
                }

                $ok = $private->writeStream($path, $stream);
                if (is_resource($stream)) {
                    fclose($stream);
                }

                if (! $ok || ! $private->exists($path)) {
                    $this->error("    FAILED to write: {$path}");
                    $failed++;

                    continue;
                }

                if (! $keepPublic) {
                    $public->delete($path);
                }

                $moved++;
            }
        }

        $this->newLine();
        $verb = $dryRun ? 'would move' : 'moved';
        $this->info("Done. {$verb}: {$moved}, already-private: {$skipped}, unreadable: {$missing}, failed: {$failed}");

        if ($dryRun) {
            $this->comment('Dry run — no files were copied or deleted. Re-run without --dry-run to apply.');
        } elseif (! $keepPublic && $moved > 0) {
            $this->comment('Public originals deleted. Verify display via signed URLs on staging before deploying.');
        }

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
