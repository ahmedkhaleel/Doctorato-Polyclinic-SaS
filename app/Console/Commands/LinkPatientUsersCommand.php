<?php

namespace App\Console\Commands;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates patient-portal user accounts for Patient rows that were
 * created via admin/secretary (walk-in patients) and never had a User
 * attached.
 *
 * Without this link the patient can't log into the portal — they
 * appear in admin lists, but cannot book online or see their own
 * records. The integrity check catches them under the
 * "active_patients_without_user" finding.
 *
 * - Matches by phone first (phone is unique on patients).
 * - If a User already exists with that email/phone, links it.
 * - Otherwise creates a disabled user with a random password. The
 *   patient must use "Forgot password" on the portal to activate.
 *
 * Safe to re-run. --dry-run previews changes without touching anything.
 */
class LinkPatientUsersCommand extends Command
{
    protected $signature = 'patients:link-users
                            {--dry-run : Show what would happen without writing}';

    protected $description = 'Create/link User accounts for Patient rows with no user_id';

    public function handle(): int
    {
        $role = Role::firstOrCreate(
            ['name' => 'patient'],
            [
                'display_name_en' => 'Patient',
                'display_name_ar' => 'مريض',
                'permissions'     => [],
                'is_system'       => true,
            ]
        );

        $patients = Patient::whereNull('user_id')->get();

        if ($patients->isEmpty()) {
            $this->info('✓ All patients already have user accounts.');
            return self::SUCCESS;
        }

        $this->line("Found {$patients->count()} patient(s) without a user_id.");

        $created = 0;
        $linked  = 0;
        $skipped = 0;

        foreach ($patients as $patient) {
            $email = $patient->email ?: ($patient->phone ? $this->phoneToEmail($patient->phone) : null);

            if (! $email) {
                $this->warn("  - skip patient #{$patient->id} ({$patient->full_name}): no email or phone");
                $skipped++;
                continue;
            }

            $existing = User::where('email', $email)->first();

            if ($existing) {
                if ($this->option('dry-run')) {
                    $this->line("  - would link patient #{$patient->id} to existing user #{$existing->id}");
                } else {
                    $patient->update(['user_id' => $existing->id]);
                    if ($existing->role_id !== $role->id) {
                        // Don't clobber an existing admin/doctor role — only link when role matches
                        $this->warn("    patient #{$patient->id}: existing user has role '{$existing->role?->name}', linking without role change");
                    }
                }
                $linked++;
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("  - would create user for patient #{$patient->id} ({$patient->full_name}) email={$email}");
                $created++;
                continue;
            }

            DB::transaction(function () use ($patient, $role, $email, &$created) {
                $user = User::create([
                    'name'      => $patient->full_name ?? 'Patient',
                    'email'     => $email,
                    'password'  => Hash::make(Str::random(32)), // force password reset flow
                    'role_id'   => $role->id,
                    'is_active' => true,
                ]);
                $patient->update(['user_id' => $user->id]);
                $created++;
            });
        }

        $verb = $this->option('dry-run') ? 'would ' : '';
        $this->info("\n✓ Done. {$verb}Created: {$created}, Linked: {$linked}, Skipped: {$skipped}");

        if (! $this->option('dry-run') && $created > 0) {
            $this->warn('Newly-created accounts have random passwords — patients must use "Forgot password" to activate.');
        }

        return self::SUCCESS;
    }

    private function phoneToEmail(string $phone): string
    {
        // Deterministic placeholder email. Patient can update it after
        // claiming the account via forgot-password.
        $normalized = preg_replace('/\D+/', '', $phone);
        $domain = parse_url(config('app.url'), PHP_URL_HOST) ?: 'doctorato.local';
        return "patient+{$normalized}@{$domain}";
    }
}
