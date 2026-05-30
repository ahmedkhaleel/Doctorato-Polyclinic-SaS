<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class BackupController extends Controller
{
    /**
     * Display the backup management page with list of existing backups.
     */
    public function index()
    {
        $backups = $this->getBackupList();

        return Inertia::render('Admin/Backups/Index', [
            'backups' => $backups,
            'summary' => $this->buildSummary($backups),
        ]);
    }

    /**
     * Freshness/health summary so the page shows "last backup X ago / stale"
     * rather than just a file list. Mirrors the backup.monitor max-age check
     * (MaximumAgeInDays => 1) so the UI and the daily backup:monitor agree.
     */
    private function buildSummary(array $backups): array
    {
        if (empty($backups)) {
            return [
                'count' => 0,
                'last_backup_at' => null,
                'last_backup_age_hours' => null,
                'is_stale' => true,
                'max_age_hours' => 24,
                'total_size' => '0 B',
            ];
        }

        // getBackupList() already sorts newest-first by date.
        $lastDate = $backups[0]['date'];
        $ageHours = (int) round((time() - strtotime($lastDate)) / 3600);
        $totalBytes = array_sum(array_column($backups, 'size_bytes'));

        return [
            'count' => count($backups),
            'last_backup_at' => $lastDate,
            'last_backup_age_hours' => $ageHours,
            'is_stale' => $ageHours > 24,
            'max_age_hours' => 24,
            'total_size' => $this->formatBytes($totalBytes),
        ];
    }

    /**
     * Trigger a full backup (DB + files).
     */
    public function runFull(Request $request)
    {
        try {
            Artisan::call('backup:run');
            $output = Artisan::output();

            Log::info('Manual full backup triggered', [
                'user_id' => auth()->id(),
                'output' => $output,
            ]);

            AuditLogger::log('created', null, ['type' => 'full'], 'Triggered manual full backup');

            return back()->with('success', 'تم بدء النسخ الاحتياطي الكامل بنجاح.');
        } catch (\Throwable $e) {
            Log::error('Manual full backup failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'فشل النسخ الاحتياطي: '.$e->getMessage());
        }
    }

    /**
     * Trigger a DB-only backup.
     */
    public function runDatabase(Request $request)
    {
        try {
            Artisan::call('backup:run', ['--only-db' => true]);
            $output = Artisan::output();

            Log::info('Manual DB backup triggered', [
                'user_id' => auth()->id(),
                'output' => $output,
            ]);

            AuditLogger::log('created', null, ['type' => 'database'], 'Triggered manual database backup');

            return back()->with('success', 'تم بدء نسخ قاعدة البيانات بنجاح.');
        } catch (\Throwable $e) {
            Log::error('Manual DB backup failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'فشل نسخ قاعدة البيانات: '.$e->getMessage());
        }
    }

    /**
     * Download a specific backup file.
     */
    public function download(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        // Prevent path traversal — only allow files within the backup directory
        $appName = config('backup.backup.name');
        if (! str_starts_with($path, $appName.'/') || str_contains($path, '..')) {
            return back()->with('error', 'مسار غير صالح.');
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            return back()->with('error', 'ملف النسخة الاحتياطية غير موجود.');
        }

        Log::info('Backup downloaded', [
            'user_id' => auth()->id(),
            'path' => $path,
        ]);

        AuditLogger::log('downloaded', null, ['path' => $path], 'Downloaded backup file: '.basename($path));

        return $disk->download($path);
    }

    /**
     * Delete a specific backup file.
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'path' => 'required|string',
        ]);

        $path = $request->input('path');

        // Prevent path traversal — only allow files within the backup directory
        $appName = config('backup.backup.name');
        if (! str_starts_with($path, $appName.'/') || str_contains($path, '..')) {
            return back()->with('error', 'مسار غير صالح.');
        }

        $disk = Storage::disk('local');

        if (! $disk->exists($path)) {
            return back()->with('error', 'ملف النسخة الاحتياطية غير موجود.');
        }

        $disk->delete($path);

        Log::info('Backup deleted', [
            'user_id' => auth()->id(),
            'path' => $path,
        ]);

        AuditLogger::log('deleted', null, ['path' => $path], 'Deleted backup file: '.basename($path));

        return back()->with('success', 'تم حذف النسخة الاحتياطية.');
    }

    /**
     * Run cleanup to remove old backups.
     */
    public function cleanup()
    {
        try {
            Artisan::call('backup:clean');

            return back()->with('success', 'تم تنظيف النسخ الاحتياطية القديمة.');
        } catch (\Throwable $e) {
            return back()->with('error', 'فشل التنظيف: '.$e->getMessage());
        }
    }

    /**
     * Get list of backup files from storage.
     */
    private function getBackupList(): array
    {
        $disk = Storage::disk('local');
        $appName = config('backup.backup.name');
        $backupDir = $appName;

        if (! $disk->exists($backupDir)) {
            return [];
        }

        $files = $disk->files($backupDir);
        $backups = [];

        foreach ($files as $file) {
            if (str_ends_with($file, '.zip')) {
                $backups[] = [
                    'path' => $file,
                    'filename' => basename($file),
                    'size' => $this->formatBytes($disk->size($file)),
                    'size_bytes' => $disk->size($file),
                    'date' => date('Y-m-d H:i:s', $disk->lastModified($file)),
                ];
            }
        }

        // Sort by date descending (newest first)
        usort($backups, fn ($a, $b) => $b['size_bytes'] <=> $a['size_bytes']);
        usort($backups, fn ($a, $b) => strcmp($b['date'], $a['date']));

        return $backups;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }
}
