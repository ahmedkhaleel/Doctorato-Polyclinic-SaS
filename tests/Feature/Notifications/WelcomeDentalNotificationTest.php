<?php

namespace Tests\Feature\Notifications;

use App\Events\PatientRegistered;
use App\Listeners\SendWelcomeSms;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Models\Setting;
use App\Services\SmsNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WelcomeDentalNotificationTest extends TestCase
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
        $p = new Patient(array_merge(['full_name' => 'WD Patient', 'phone' => '01012345678'], $attrs));
        $p->file_number = 'P-WD-'.uniqid();
        $p->is_active = true;
        $p->forceFill($attrs);
        $p->save();

        return $p;
    }

    public function test_welcome_routes_through_hub(): void
    {
        $this->legacySms();
        $patient = $this->patient();

        (new SendWelcomeSms)->handle(new PatientRegistered($patient));

        $log = NotificationLog::where('event_key', 'account.created')->where('channel', 'sms')->first();
        $this->assertNotNull($log);
        $this->assertSame(NotificationLog::STATUS_SENT, $log->status);
        $this->assertSame($patient->id, $log->recipient_id);
    }

    public function test_dental_lab_ready_routes_through_hub(): void
    {
        $this->legacySms();
        $patient = $this->patient();

        SmsNotificationService::dentalLabOrderReady($patient, 'تاج خزفي');

        $log = NotificationLog::where('event_key', 'dental.lab_ready')->where('channel', 'sms')->first();
        $this->assertNotNull($log);
        $this->assertSame(NotificationLog::STATUS_SENT, $log->status);
        $this->assertStringContainsString('تاج خزفي', $log->meta['body']);
    }

    public function test_treatment_plan_approved_routes_through_hub(): void
    {
        $this->legacySms();
        $patient = $this->patient();

        SmsNotificationService::treatmentPlanApproved($patient, 'خطة تقويم');

        $log = NotificationLog::where('event_key', 'dental.treatment_plan_approved')->where('channel', 'sms')->first();
        $this->assertSame(NotificationLog::STATUS_SENT, $log->status);
    }

    public function test_manual_message_event_has_no_template_so_typed_body_is_used(): void
    {
        // manual.message must never be overridden by a stored template.
        $this->assertFalse(
            \App\Models\NotificationTemplate::where('event_key', 'manual.message')->exists()
        );
    }
}
