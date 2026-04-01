<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ProcessBookingSmsJob;
use App\Models\Booking;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ProcessBookingSmsJobTest extends TestCase
{
    public function test_booking_sms_job_can_be_dispatched(): void
    {
        Queue::fake();

        $booking = new Booking([
            'full_name' => 'Test Patient',
            'phone' => '+966500000000',
            'booking_type' => 'dermatology_consultation',
        ]);
        $booking->id = 999;

        ProcessBookingSmsJob::dispatch($booking, 'confirmed');

        Queue::assertPushed(ProcessBookingSmsJob::class, function (ProcessBookingSmsJob $job) {
            return $job->type === 'confirmed';
        });
    }

    public function test_booking_sms_job_accepts_different_types(): void
    {
        Queue::fake();

        $booking = new Booking([
            'full_name' => 'Test Patient',
            'phone' => '+966500000000',
            'booking_type' => 'cosmetic_consultation',
        ]);
        $booking->id = 1;

        $types = ['confirmed', 'reminder', 'same_day'];

        foreach ($types as $type) {
            ProcessBookingSmsJob::dispatch($booking, $type);
        }

        Queue::assertPushed(ProcessBookingSmsJob::class, 3);

        foreach ($types as $type) {
            Queue::assertPushed(ProcessBookingSmsJob::class, function (ProcessBookingSmsJob $job) use ($type) {
                return $job->type === $type;
            });
        }
    }

    public function test_booking_sms_job_has_booking_tags(): void
    {
        $booking = new Booking([
            'full_name' => 'Test Patient',
            'phone' => '+966500000000',
            'booking_type' => 'service',
        ]);
        $booking->id = 42;

        $job = new ProcessBookingSmsJob($booking, 'confirmed');

        $tags = $job->tags();

        $this->assertContains('sms', $tags);
        $this->assertContains('booking', $tags);
        $this->assertContains('booking:42', $tags);
        $this->assertContains('type:confirmed', $tags);
    }

    public function test_booking_sms_job_deletes_when_booking_missing(): void
    {
        $booking = new Booking([
            'full_name' => 'Test Patient',
            'phone' => '+966500000000',
            'booking_type' => 'dermatology_consultation',
        ]);
        $booking->id = 1;

        $job = new ProcessBookingSmsJob($booking);

        $this->assertTrue($job->deleteWhenMissingModels);
    }

    public function test_booking_sms_job_default_type_is_confirmed(): void
    {
        $booking = new Booking([
            'full_name' => 'Test Patient',
            'phone' => '+966500000000',
            'booking_type' => 'dermatology_consultation',
        ]);
        $booking->id = 1;

        $job = new ProcessBookingSmsJob($booking);

        $this->assertEquals('confirmed', $job->type);
    }

    public function test_booking_sms_job_has_retry_configuration(): void
    {
        $booking = new Booking([
            'full_name' => 'Test Patient',
            'phone' => '+966500000000',
            'booking_type' => 'dermatology_consultation',
        ]);
        $booking->id = 1;

        $job = new ProcessBookingSmsJob($booking);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([30, 60, 120], $job->backoff);
    }
}
