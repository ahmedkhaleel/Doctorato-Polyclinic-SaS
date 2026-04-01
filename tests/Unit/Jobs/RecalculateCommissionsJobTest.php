<?php

namespace Tests\Unit\Jobs;

use App\Jobs\RecalculateCommissionsJob;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecalculateCommissionsJobTest extends TestCase
{
    public function test_commission_job_can_be_dispatched(): void
    {
        Queue::fake();

        RecalculateCommissionsJob::dispatch();

        Queue::assertPushed(RecalculateCommissionsJob::class);
    }

    public function test_commission_job_is_unique(): void
    {
        $job = new RecalculateCommissionsJob();

        $this->assertInstanceOf(ShouldBeUnique::class, $job);
    }

    public function test_commission_job_has_10_minute_timeout(): void
    {
        $job = new RecalculateCommissionsJob();

        $this->assertEquals(600, $job->timeout);
    }

    public function test_commission_job_has_unique_lock_duration(): void
    {
        $job = new RecalculateCommissionsJob();

        $this->assertEquals(900, $job->uniqueFor);
    }

    public function test_commission_job_accepts_null_visit_id(): void
    {
        $job = new RecalculateCommissionsJob();
        $this->assertNull($job->visitId);
    }

    public function test_commission_job_accepts_specific_visit_id(): void
    {
        $job = new RecalculateCommissionsJob(42);
        $this->assertEquals(42, $job->visitId);
    }

    public function test_commission_job_has_correct_tags_for_bulk(): void
    {
        $job = new RecalculateCommissionsJob();

        $tags = $job->tags();

        $this->assertContains('commission', $tags);
        $this->assertContains('heavy-computation', $tags);
        $this->assertContains('bulk', $tags);
    }

    public function test_commission_job_has_correct_tags_for_single_visit(): void
    {
        $job = new RecalculateCommissionsJob(55);

        $tags = $job->tags();

        $this->assertContains('commission', $tags);
        $this->assertContains('heavy-computation', $tags);
        $this->assertContains('visit:55', $tags);
        $this->assertNotContains('bulk', $tags);
    }

    public function test_commission_job_tries_once(): void
    {
        $job = new RecalculateCommissionsJob();

        $this->assertEquals(1, $job->tries);
    }
}
