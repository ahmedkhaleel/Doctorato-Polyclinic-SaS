<?php

namespace Tests\Feature\Notifications;

use App\Models\NotificationChannel;
use App\Models\NotificationLog;
use App\Models\NotificationSequence;
use App\Models\NotificationSequenceEnrollment as Enrollment;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use App\Services\Notifications\Notifier;
use App\Services\Notifications\SequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SequenceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function smsReady(): void
    {
        Setting::set('sms_enabled', '1');
        Setting::set('sms_provider', 'twilio');
        Setting::set('sms_twilio_account_sid', 'AC');
        Setting::set('sms_twilio_auth_token', 'tok');
        Setting::set('sms_twilio_from_number', '+1');
        NotificationChannel::where('channel', 'sms')->update(['enabled' => true]);
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);
    }

    private function patient(array $attrs = []): Patient
    {
        $p = new Patient(array_merge(['full_name' => 'Seq', 'phone' => '01012345678'], $attrs));
        $p->file_number = 'P-SEQ-'.uniqid();
        $p->is_active = true;
        $p->forceFill($attrs)->save();

        return $p;
    }

    private function sequence(?string $trigger = null): NotificationSequence
    {
        $seq = NotificationSequence::create(['name' => 'Welcome', 'trigger_event' => $trigger, 'is_active' => true]);
        $seq->steps()->create(['position' => 0, 'delay_minutes' => 0, 'channel' => 'sms', 'body_ar' => 'الخطوة 1']);
        $seq->steps()->create(['position' => 1, 'delay_minutes' => 60, 'channel' => 'sms', 'body_ar' => 'الخطوة 2']);

        return $seq->load('steps');
    }

    public function test_enroll_creates_active_enrollment(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));
        $seq = $this->sequence();
        $p = $this->patient();

        $e = app(SequenceService::class)->enroll($seq, $p);
        $this->assertNotNull($e);
        $this->assertSame(0, $e->current_step);
        $this->assertSame('active', $e->status);
        $this->assertSame('2026-06-01 12:00:00', $e->next_run_at->format('Y-m-d H:i:s')); // delay 0
    }

    public function test_enroll_is_idempotent(): void
    {
        $seq = $this->sequence();
        $p = $this->patient();
        $svc = app(SequenceService::class);

        $this->assertNotNull($svc->enroll($seq, $p));
        $this->assertNull($svc->enroll($seq, $p)); // already active
        $this->assertSame(1, Enrollment::where('sequence_id', $seq->id)->count());
    }

    public function test_advance_sends_step_and_progresses_then_completes(): void
    {
        $this->smsReady();
        Carbon::setTestNow(Carbon::parse('2026-06-01 12:00:00'));
        $seq = $this->sequence();
        $p = $this->patient(['notify_sms_marketing' => true]);
        $svc = app(SequenceService::class);
        $svc->enroll($seq, $p);

        // Step 1 due now.
        $this->assertSame(1, $svc->advanceDue());
        $e = Enrollment::first();
        $this->assertSame(1, $e->current_step);
        $this->assertSame('active', $e->status);
        $this->assertSame('2026-06-01 13:00:00', $e->next_run_at->format('Y-m-d H:i:s')); // +60 min
        $this->assertSame(1, NotificationLog::where('event_key', 'sequence.message')->where('status', 'sent')->count());

        // Not due yet.
        $this->assertSame(0, $svc->advanceDue());

        // Jump 1h → step 2 fires → completed.
        Carbon::setTestNow(Carbon::parse('2026-06-01 13:05:00'));
        $this->assertSame(1, $svc->advanceDue());
        $this->assertSame('completed', Enrollment::first()->status);
        $this->assertSame(2, NotificationLog::where('event_key', 'sequence.message')->where('status', 'sent')->count());
    }

    public function test_auto_enroll_on_trigger_event(): void
    {
        $seq = $this->sequence('lead.welcome');
        $p = $this->patient();

        Notifier::eventNow('lead.welcome', $p, ['body' => 'hi']);

        $this->assertSame(1, Enrollment::where('sequence_id', $seq->id)->where('recipient_id', $p->id)->count());
    }

    public function test_worker_command_runs(): void
    {
        $this->smsReady();
        $seq = $this->sequence();
        $p = $this->patient(['notify_sms_marketing' => true]);
        app(SequenceService::class)->enroll($seq, $p);

        $this->artisan('notifications:run-sequences')->assertExitCode(0);
        $this->assertSame(1, Enrollment::first()->current_step);
    }

    public function test_admin_can_create_and_enroll(): void
    {
        $role = Role::firstOrCreate(['name' => 'super_admin'],
            ['display_name_en' => 'S', 'display_name_ar' => 'م', 'permissions' => ['*'], 'is_system' => true]);
        $admin = User::create(['name' => 'A', 'email' => 'sq-'.uniqid().'@t.com',
            'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
        $p = $this->patient();

        $this->actingAs($admin)->post('/admin/notification-sequences', [
            'name' => 'Onboarding', 'is_active' => true,
            'steps' => [['delay_minutes' => 0, 'channel' => 'sms', 'body_ar' => 'مرحبا']],
        ])->assertRedirect();

        $seq = NotificationSequence::where('name', 'Onboarding')->first();
        $this->assertNotNull($seq);
        $this->assertCount(1, $seq->steps);

        $this->actingAs($admin)->post("/admin/notification-sequences/{$seq->id}/enroll", ['patient_id' => $p->id])->assertRedirect();
        $this->assertSame(1, Enrollment::where('sequence_id', $seq->id)->count());
    }
}
