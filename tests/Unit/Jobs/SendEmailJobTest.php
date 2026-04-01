<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendEmailJob;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendEmailJobTest extends TestCase
{
    public function test_email_job_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        $mailable = new class extends Mailable
        {
            public function build(): self
            {
                return $this->subject('Test')->html('<p>Test</p>');
            }
        };

        SendEmailJob::dispatch('test@example.com', $mailable, 'booking_created');

        Queue::assertPushed(SendEmailJob::class, function (SendEmailJob $job) {
            return $job->to === 'test@example.com'
                && $job->context === 'booking_created';
        });
    }

    public function test_email_job_has_correct_tags(): void
    {
        $mailable = new class extends Mailable
        {
            public function build(): self
            {
                return $this->subject('Test')->html('<p>Test</p>');
            }
        };

        $job = new SendEmailJob('admin@clinic.com', $mailable, 'invoice');

        $tags = $job->tags();

        $this->assertContains('email', $tags);
        $this->assertContains('to:admin@clinic.com', $tags);
        $this->assertContains('context:invoice', $tags);
    }

    public function test_email_job_tags_default_context_is_general(): void
    {
        $mailable = new class extends Mailable
        {
            public function build(): self
            {
                return $this->subject('Test')->html('<p>Test</p>');
            }
        };

        $job = new SendEmailJob('admin@clinic.com', $mailable);

        $tags = $job->tags();

        $this->assertContains('context:general', $tags);
    }

    public function test_email_job_has_retry_configuration(): void
    {
        $mailable = new class extends Mailable
        {
            public function build(): self
            {
                return $this->subject('Test')->html('<p>Test</p>');
            }
        };

        $job = new SendEmailJob('test@example.com', $mailable);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([30, 60, 180], $job->backoff);
    }
}
