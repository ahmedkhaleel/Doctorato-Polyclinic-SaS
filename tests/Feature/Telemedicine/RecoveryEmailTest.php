<?php

namespace Tests\Feature\Telemedicine;

use App\Models\Doctor;
use App\Models\OnlineConsultation;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * SendConsultationRecoveryCommand invariants:
 *  - Identifies pending consultations in the (30min, 12h) window
 *  - Skips < 30min (too fresh) and > 12h (will be cleaned up anyway)
 *  - Sends only once per consultation (recovery_email_sent_at gate)
 *  - Honors patient.notify_email_bookings opt-out
 *  - --dry-run doesn't write
 */
class RecoveryEmailTest extends TestCase
{
    use RefreshDatabase;

    private function makeConsultation(int $minutesOld, array $overrides = []): OnlineConsultation
    {
        $role = Role::firstOrCreate(['name' => 'patient'],
            ['display_name_en' => 'P', 'display_name_ar' => 'م', 'permissions' => [], 'is_system' => true]);
        $user = User::create([
            'name' => 'X', 'email' => 'x-' . random_int(1000, 9999) . '@test.com',
            'password' => bcrypt('p'), 'role_id' => $role->id, 'is_active' => true,
        ]);
        $patient = Patient::create([
            'user_id' => $user->id, 'full_name' => 'X', 'phone' => '+971500000000',
            'email' => $user->email, 'is_active' => true,
            'notify_email_bookings' => $overrides['notify_email_bookings'] ?? true,
        ]);
        $doctor = Doctor::create([
            'name_ar' => 'د', 'name_en' => 'Dr',
            'doctor_type' => 'specialist', 'status' => 'active',
            'online_consultation_enabled' => true, 'online_consultation_fee' => 250,
        ]);

        $c = OnlineConsultation::create([
            'consultation_number' => 'OC-' . random_int(10000, 99999),
            'patient_id'      => $patient->id,
            'doctor_id'       => $doctor->id,
            'scheduled_date'  => now()->addDays(2)->format('Y-m-d'),
            'start_time'      => '14:00',
            'end_time'        => '14:30',
            'module'          => 'derma',
            'status'          => 'scheduled',
            'fee'             => 250,
            'payment_status'  => $overrides['payment_status'] ?? 'pending',
        ]);

        // Force created_at — Eloquent ignores assignments via create()
        \DB::table('online_consultations')->where('id', $c->id)->update([
            'created_at' => now()->subMinutes($minutesOld),
        ]);

        return $c->fresh();
    }

    public function test_consultation_in_window_gets_emailed(): void
    {
        Mail::fake();

        $c = $this->makeConsultation(60);  // 1 hour old — in window

        Artisan::call('telemedicine:send-recovery-emails');

        $c->refresh();
        $this->assertNotNull($c->recovery_email_sent_at);
        Mail::assertSent(\Illuminate\Mail\Mailable::class);
    }

    public function test_too_fresh_consultation_is_skipped(): void
    {
        Mail::fake();

        $c = $this->makeConsultation(15);  // 15 min — below floor

        Artisan::call('telemedicine:send-recovery-emails');

        $c->refresh();
        $this->assertNull($c->recovery_email_sent_at);
    }

    public function test_too_old_consultation_is_skipped(): void
    {
        Mail::fake();

        $c = $this->makeConsultation(720 + 60);  // 13 hours — above ceiling

        Artisan::call('telemedicine:send-recovery-emails');

        $c->refresh();
        $this->assertNull($c->recovery_email_sent_at);
    }

    public function test_paid_consultation_is_skipped(): void
    {
        Mail::fake();

        $c = $this->makeConsultation(60, ['payment_status' => 'paid']);

        Artisan::call('telemedicine:send-recovery-emails');

        $c->refresh();
        $this->assertNull($c->recovery_email_sent_at);
    }

    public function test_opted_out_patient_is_skipped(): void
    {
        Mail::fake();

        $c = $this->makeConsultation(60, ['notify_email_bookings' => false]);

        Artisan::call('telemedicine:send-recovery-emails');

        $c->refresh();
        $this->assertNull($c->recovery_email_sent_at);
    }

    public function test_already_emailed_consultation_is_skipped(): void
    {
        Mail::fake();

        $c = $this->makeConsultation(60);
        $c->update(['recovery_email_sent_at' => now()->subMinutes(10)]);
        $first = $c->recovery_email_sent_at;

        Artisan::call('telemedicine:send-recovery-emails');

        $c->refresh();
        // Timestamp unchanged → command did not touch this row again
        $this->assertEquals(
            $first->format('Y-m-d H:i:s'),
            $c->recovery_email_sent_at->format('Y-m-d H:i:s')
        );
    }

    public function test_dry_run_does_not_write(): void
    {
        Mail::fake();

        $c = $this->makeConsultation(60);

        Artisan::call('telemedicine:send-recovery-emails', ['--dry-run' => true]);

        $c->refresh();
        $this->assertNull($c->recovery_email_sent_at);
    }
}
