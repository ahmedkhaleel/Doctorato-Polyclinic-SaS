<?php

namespace Tests\Feature\Notifications;

use App\Models\Booking;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Setting;
use App\Models\Visit;
use App\Services\SmsNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VisitRecallNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function legacySms(): void
    {
        Setting::set('sms_enabled', '1');
        Setting::set('sms_provider', 'twilio');
        Setting::set('sms_twilio_account_sid', 'AC');
        Setting::set('sms_twilio_auth_token', 'tok');
        Setting::set('sms_twilio_from_number', '+1');
        Http::fake(['*' => Http::response(['sid' => 'SM1'], 201)]);
    }

    private function patient(array $attrs = []): Patient
    {
        $p = new Patient(array_merge(['full_name' => 'Recall Patient', 'phone' => '01012345678'], $attrs));
        $p->file_number = 'P-VR-'.uniqid();
        $p->is_active = true;
        $p->forceFill($attrs);
        $p->save();

        return $p;
    }

    public function test_visit_completed_routes_through_hub(): void
    {
        $this->legacySms();
        $patient = $this->patient();
        $booking = Booking::create([
            'patient_id' => $patient->id, 'booking_number' => 'BK-'.uniqid(),
            'module' => 'dental', 'preferred_date' => now()->toDateString(),
            'phone' => $patient->phone, 'full_name' => $patient->full_name,
        ]);
        $visit = Visit::create([
            'patient_id' => $patient->id, 'booking_id' => $booking->id,
            'visit_type' => 'consultation', 'status' => 'completed',
            'visit_date' => now()->toDateString(),
        ]);

        SmsNotificationService::visitCompleted($visit);

        $log = NotificationLog::where('event_key', 'visit.completed')->where('channel', 'sms')->first();
        $this->assertNotNull($log);
        $this->assertSame(NotificationLog::STATUS_SENT, $log->status);
        $this->assertSame($patient->id, $log->recipient_id);
    }

    public function test_dental_recall_respects_reminder_consent(): void
    {
        $this->legacySms();
        $patient = $this->patient(['notify_sms_reminders' => false]);

        SmsNotificationService::dentalRecallReminder($patient, 'cleaning', 6);

        $log = NotificationLog::where('event_key', 'dental.followup')->where('channel', 'sms')->first();
        $this->assertSame(NotificationLog::STATUS_SKIPPED, $log->status);
        $this->assertSame('no consent', $log->error);
    }

    public function test_derma_recall_sends_when_consented(): void
    {
        $this->legacySms();
        $patient = $this->patient(); // reminders default ON

        SmsNotificationService::dermaRecallReminder($patient, 8);

        $log = NotificationLog::where('event_key', 'derma.recall')->where('channel', 'sms')->first();
        $this->assertSame(NotificationLog::STATUS_SENT, $log->status);
        // derma.recall has no seeded template → the rich inline body is used.
        $this->assertStringContainsString('بشرتك', $log->meta['body']);
    }
}
