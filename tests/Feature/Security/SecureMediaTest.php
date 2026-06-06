<?php

namespace Tests\Feature\Security;

use App\Models\Role;
use App\Models\User;
use App\Support\SecureMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * S1 — authenticated, signed serving of sensitive files. Proves the primitive:
 * a valid signed URL streams the file only for an authenticated user; anonymous
 * users and tampered/unsigned URLs are rejected; traversal/bad disks are 404.
 */
class SecureMediaTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $role = Role::firstOrCreate(['name' => 'admin'], ['display_name_en' => 'A', 'display_name_ar' => 'A', 'permissions' => ['*'], 'is_system' => true]);

        return User::create(['name' => 'A', 'email' => 'media-'.uniqid().'@test.com', 'password' => bcrypt('x'), 'role_id' => $role->id, 'is_active' => true]);
    }

    public function test_authenticated_user_can_stream_a_signed_private_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('patient-documents/9/report.pdf', 'PHI');

        $url = SecureMedia::url('patient-documents/9/report.pdf', 'local');

        $resp = $this->actingAs($this->user())->get($url)->assertOk();
        $this->assertSame('PHI', $resp->streamedContent());
    }

    public function test_anonymous_user_is_blocked_even_with_a_valid_signature(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('patient-documents/9/report.pdf', 'PHI');

        // Valid signature, but no authenticated session → 403.
        $this->get(SecureMedia::url('patient-documents/9/report.pdf', 'local'))
            ->assertForbidden();
    }

    public function test_tampered_or_unsigned_url_is_rejected(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('x/y.pdf', 'PHI');

        // Unsigned direct hit → 403 (signed middleware).
        $this->actingAs($this->user())->get('/media?disk=local&path=x/y.pdf')->assertForbidden();

        // Tampered signature → 403.
        $tampered = SecureMedia::url('x/y.pdf', 'local').'TAMPER';
        $this->actingAs($this->user())->get($tampered)->assertForbidden();
    }

    public function test_path_traversal_and_bad_disk_are_404(): void
    {
        Storage::fake('local');
        $u = $this->user();

        $this->actingAs($u)->get(SecureMedia::url('../../.env', 'local'))->assertNotFound();
        $this->actingAs($u)->get(SecureMedia::url('some/file.pdf', 's3'))->assertNotFound();
    }

    public function test_helper_returns_null_for_empty_path(): void
    {
        $this->assertNull(SecureMedia::url(null));
        $this->assertNull(SecureMedia::url(''));
    }

    public function test_dual_disk_helpers_fall_back_to_legacy_public_disk(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        // A file that has NOT been migrated yet lives only on the public disk.
        Storage::disk('public')->put('visit-photos/old.jpg', 'LEGACY');

        $this->assertTrue(SecureMedia::exists('visit-photos/old.jpg'));
        $this->assertSame('public', SecureMedia::diskFor('visit-photos/old.jpg'));

        // Deletion finds it on the public disk.
        $this->assertTrue(SecureMedia::delete('visit-photos/old.jpg'));
        $this->assertFalse(SecureMedia::exists('visit-photos/old.jpg'));
    }

    public function test_dual_disk_prefers_private_when_present_on_both(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('derma/photos/p.jpg', 'OLD');
        Storage::disk('local')->put('derma/photos/p.jpg', 'NEW');

        $this->assertSame('local', SecureMedia::diskFor('derma/photos/p.jpg'));
    }

    public function test_migration_command_moves_public_files_to_private_and_is_idempotent(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('dental-xrays/a.png', 'XR');
        Storage::disk('public')->put('patient-documents/3/r.pdf', 'DOC');
        // A non-PHI prefix must be left untouched.
        Storage::disk('public')->put('uploads/testimonials/t.jpg', 'PUB');

        $this->artisan('media:migrate-phi')->assertExitCode(0);

        // PHI files now on private, gone from public.
        $this->assertTrue(Storage::disk('local')->exists('dental-xrays/a.png'));
        $this->assertTrue(Storage::disk('local')->exists('patient-documents/3/r.pdf'));
        $this->assertFalse(Storage::disk('public')->exists('dental-xrays/a.png'));

        // Non-PHI untouched.
        $this->assertTrue(Storage::disk('public')->exists('uploads/testimonials/t.jpg'));
        $this->assertFalse(Storage::disk('local')->exists('uploads/testimonials/t.jpg'));

        // Re-running is a no-op (idempotent), still exit 0.
        $this->artisan('media:migrate-phi')->assertExitCode(0);
        $this->assertTrue(Storage::disk('local')->exists('dental-xrays/a.png'));
    }

    public function test_dry_run_moves_nothing(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        Storage::disk('public')->put('visit-photos/v.jpg', 'V');

        $this->artisan('media:migrate-phi --dry-run')->assertExitCode(0);

        $this->assertTrue(Storage::disk('public')->exists('visit-photos/v.jpg'));
        $this->assertFalse(Storage::disk('local')->exists('visit-photos/v.jpg'));
    }

    public function test_model_accessor_emits_signed_media_url(): void
    {
        $photo = new \App\Models\VisitPhoto(['photo_path' => 'visit-photos/x.jpg']);

        $url = $photo->url;
        $this->assertNotNull($url);
        $this->assertStringContainsString('/media', $url);
        $this->assertStringContainsString('signature=', $url);
        $this->assertStringContainsString('path=visit-photos', $url);
    }

    public function test_patient_photo_url_is_signed_with_longer_avatar_expiry(): void
    {
        $patient = new \App\Models\Patient(['photo' => 'uploads/patients/p.jpg']);

        $url = $patient->photo_url;
        $this->assertNotNull($url);
        $this->assertStringContainsString('/media', $url);
        $this->assertStringContainsString('signature=', $url);

        // Avatar expiry is the longer window, not the default 60-min one.
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        $expires = (int) ($q['expires'] ?? 0);
        $this->assertGreaterThan(
            now()->addMinutes(SecureMedia::TTL_MINUTES + 5)->timestamp,
            $expires,
            'Patient avatar URL should use the longer avatar TTL.'
        );
    }

    public function test_external_http_photo_passes_through_unsigned(): void
    {
        $url = SecureMedia::avatar('https://cdn.example.com/face.jpg');
        $this->assertSame('https://cdn.example.com/face.jpg', $url);

        $patient = new \App\Models\Patient(['photo' => 'https://cdn.example.com/face.jpg']);
        $this->assertSame('https://cdn.example.com/face.jpg', $patient->photo_url);
    }

    public function test_chat_attachment_helper_signs_path(): void
    {
        $url = SecureMedia::url('uploads/messages/file.pdf');
        $this->assertNotNull($url);
        $this->assertStringContainsString('/media', $url);
        $this->assertStringContainsString('path=uploads', $url);
    }
}
