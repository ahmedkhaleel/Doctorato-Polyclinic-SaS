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
}
