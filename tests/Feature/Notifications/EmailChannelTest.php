<?php

namespace Tests\Feature\Notifications;

use App\Mail\HubNotificationMail;
use App\Models\NotificationChannel;
use App\Models\NotificationConsent;
use App\Models\NotificationLog;
use App\Models\Patient;
use App\Services\Notifications\Notifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailChannelTest extends TestCase
{
    use RefreshDatabase;

    private function enableEmail(): void
    {
        $c = NotificationChannel::for('email');
        $c->enabled = true;
        $c->provider = 'smtp';
        $c->config = ['host' => 'smtp.test', 'username' => 'u', 'password' => 'p', 'from_address' => 'a@b.c'];
        $c->save();
    }

    private function patient(array $attrs = []): Patient
    {
        $p = new Patient(array_merge(['full_name' => 'Mail', 'phone' => '01012345678', 'email' => 'p@example.test'], $attrs));
        $p->file_number = 'P-MAIL-'.uniqid();
        $p->is_active = true;
        $p->forceFill($attrs)->save();

        return $p;
    }

    public function test_email_sends_branded_mailable(): void
    {
        Mail::fake();
        $this->enableEmail();
        $patient = $this->patient(['email' => 'p@example.test']);

        // invoice.overdue is transactional → routes email (among others).
        Notifier::eventNow('invoice.overdue', $patient, ['body' => 'فاتورة مستحقة', 'subject' => 'فاتورة', 'to' => 'p@example.test'], ['email']);

        Mail::assertSent(HubNotificationMail::class, fn ($m) => $m->hasTo('p@example.test') && $m->unsubscribeUrl === null);
        $this->assertSame(NotificationLog::STATUS_SENT,
            NotificationLog::where('channel', 'email')->where('event_key', 'invoice.overdue')->first()->status);
    }

    public function test_marketing_email_includes_unsubscribe_link(): void
    {
        Mail::fake();
        $this->enableEmail();
        $patient = $this->patient(['email' => 'm@example.test', 'notify_email_marketing' => true]);

        // lead.reactivation is marketing → unsubscribe link injected.
        Notifier::eventNow('lead.reactivation', $patient, ['body' => 'عرض', 'to' => 'm@example.test'], ['email']);

        Mail::assertSent(HubNotificationMail::class, fn ($m) => $m->unsubscribeUrl !== null && str_contains($m->unsubscribeUrl, '/unsubscribe/'.$patient->id));
    }

    public function test_unsubscribe_link_opts_out_marketing(): void
    {
        $patient = $this->patient(['notify_email_marketing' => true, 'notify_sms_marketing' => true]);
        $url = URL::temporarySignedRoute('notifications.unsubscribe', now()->addDay(), ['patient' => $patient->id]);

        $this->get($url)->assertOk()->assertSee('تم إلغاء الاشتراك');

        $patient->refresh();
        $this->assertFalse((bool) $patient->notify_email_marketing);
        $this->assertFalse((bool) $patient->notify_sms_marketing);
        $this->assertTrue(NotificationConsent::where('recipient_id', $patient->id)->where('source', 'unsubscribe_link')->exists());
    }

    public function test_unsubscribe_rejects_unsigned_url(): void
    {
        $patient = $this->patient();
        $this->get("/unsubscribe/{$patient->id}")->assertForbidden(); // 403 invalid signature
    }
}
