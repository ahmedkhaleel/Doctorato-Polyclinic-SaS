<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * P6-3 (first step) — the PWA web app manifest is public, well-typed, carries
 * the clinic name + navy theme, and declares a standalone installable app.
 */
class PwaManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_is_public_and_well_formed(): void
    {
        Setting::set('clinic_name', 'Doctorato Polyclinic', 'general');

        $resp = $this->get('/manifest.webmanifest')->assertOk();
        $this->assertStringContainsString('application/manifest+json', $resp->headers->get('content-type'));

        $resp->assertJson([
            'name' => 'Doctorato Polyclinic',
            'display' => 'standalone',
            'theme_color' => '#1B365D',
            'start_url' => '/',
        ]);
        $resp->assertJsonPath('icons.0.sizes', '180x180');
    }

    public function test_manifest_reflects_configured_clinic_name(): void
    {
        Setting::set('clinic_name', 'Markeza Clinic', 'general');

        $this->get('/manifest.webmanifest')->assertOk()->assertJsonPath('name', 'Markeza Clinic');
    }
}
