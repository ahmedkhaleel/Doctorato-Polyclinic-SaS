<?php

namespace Tests\Unit\Jobs;

use App\Jobs\SendSmsJob;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class SendSmsJobTest extends TestCase
{
    public function test_sms_job_can_be_dispatched_to_queue(): void
    {
        Queue::fake();

        SendSmsJob::dispatch('+966500000000', 'Test message', null, 'test');

        Queue::assertPushed(SendSmsJob::class, function (SendSmsJob $job) {
            return $job->phone === '+966500000000'
                && $job->message === 'Test message'
                && $job->context === 'test';
        });
    }

    public function test_sms_job_has_correct_tags(): void
    {
        $job = new SendSmsJob('+966500000000', 'Hello', null, 'booking_confirmed');

        $tags = $job->tags();

        $this->assertContains('sms', $tags);
        $this->assertContains('phone:+966500000000', $tags);
        $this->assertContains('context:booking_confirmed', $tags);
    }

    public function test_sms_job_tags_default_context_is_general(): void
    {
        $job = new SendSmsJob('+966500000000', 'Hello');

        $tags = $job->tags();

        $this->assertContains('context:general', $tags);
    }

    public function test_sms_job_has_retry_configuration(): void
    {
        $job = new SendSmsJob('+966500000000', 'Hello');

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([30, 60, 120], $job->backoff);
    }

    public function test_sms_job_is_serializable(): void
    {
        $job = new SendSmsJob('+966500000000', 'Test message', 'AuraDerma', 'test_context');

        $serialized = serialize($job);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf(SendSmsJob::class, $unserialized);
        $this->assertEquals('+966500000000', $unserialized->phone);
        $this->assertEquals('Test message', $unserialized->message);
        $this->assertEquals('AuraDerma', $unserialized->senderName);
        $this->assertEquals('test_context', $unserialized->context);
    }

    public function test_sms_job_deletes_when_missing_models(): void
    {
        $job = new SendSmsJob('+966500000000', 'Hello');

        $this->assertTrue($job->deleteWhenMissingModels);
    }

    public function test_sms_job_dispatched_with_no_sender_name(): void
    {
        Queue::fake();

        SendSmsJob::dispatch('+966500000000', 'Test message');

        Queue::assertPushed(SendSmsJob::class, function (SendSmsJob $job) {
            return $job->senderName === null
                && $job->context === null;
        });
    }
}
