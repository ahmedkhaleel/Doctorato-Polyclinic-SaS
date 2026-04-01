<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendNotificationJob;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendNotificationJobTest extends TestCase
{
    public function test_notification_job_can_be_dispatched(): void
    {
        Queue::fake();

        $notifiable = new User();
        $notification = new class extends Notification
        {
            public function via($notifiable): array
            {
                return ['database'];
            }
        };

        SendNotificationJob::dispatch($notifiable, $notification, 'test_context');

        Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job) {
            return $job->context === 'test_context';
        });
    }

    public function test_notification_job_has_correct_tags(): void
    {
        $notifiable = new User();
        $notification = new class extends Notification
        {
            public function via($notifiable): array
            {
                return ['database'];
            }
        };

        $job = new SendNotificationJob($notifiable, $notification, 'booking_alert');

        $tags = $job->tags();

        $this->assertContains('notification', $tags);
        $this->assertContains('context:booking_alert', $tags);
        // The second tag should be the notification class name
        $this->assertCount(3, $tags);
    }

    public function test_notification_job_tags_default_context_is_general(): void
    {
        $notifiable = new User();
        $notification = new class extends Notification
        {
            public function via($notifiable): array
            {
                return ['database'];
            }
        };

        $job = new SendNotificationJob($notifiable, $notification);

        $tags = $job->tags();

        $this->assertContains('context:general', $tags);
    }

    public function test_notification_job_has_retry_configuration(): void
    {
        $notifiable = new User();
        $notification = new class extends Notification
        {
            public function via($notifiable): array
            {
                return ['database'];
            }
        };

        $job = new SendNotificationJob($notifiable, $notification);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([15, 60, 120], $job->backoff);
    }

    public function test_notification_job_deletes_when_missing_models(): void
    {
        $notifiable = new User();
        $notification = new class extends Notification
        {
            public function via($notifiable): array
            {
                return ['database'];
            }
        };

        $job = new SendNotificationJob($notifiable, $notification);

        $this->assertTrue($job->deleteWhenMissingModels);
    }
}
