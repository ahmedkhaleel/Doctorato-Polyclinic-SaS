<?php

namespace Tests;

use App\Models\Setting;
use App\Services\ModuleManager;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // ModuleManager memoises module-enabled state in process-level statics.
        // RefreshDatabase resets the DB between tests but not these statics, so
        // a stale value would leak across tests and 302 module-gated routes.
        // Reset to a clean slate at the start of every test.
        ModuleManager::flushStaticCache();

        // Setting also memoises in a process-level static cache; flush it so one
        // test's Setting::set() can't leak into the next (e.g. SMS credentials).
        Setting::clearCache();
    }
}
