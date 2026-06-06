<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression: the @routes (Ziggy) directive must emit window.route + the named
 * route map into the root document, so Vue pages that call route(...) — derma,
 * neuropsych, obgyn, etc. — resolve instead of throwing "route is not a
 * function" and rendering blank. Pairs with the app.js globalProperties.route
 * bridge that exposes route() inside <template> scope.
 */
class ZiggyRoutesSharedTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_document_exposes_ziggy_route_map(): void
    {
        $html = $this->get('/ar')->assertOk()->getContent();

        // Ziggy emits a global Ziggy object + the route() function.
        $this->assertStringContainsString('Ziggy', $html);
        // And the named routes the SPA relies on are present.
        $this->assertStringContainsString('doctor.derma.dashboard', $html);
    }
}
