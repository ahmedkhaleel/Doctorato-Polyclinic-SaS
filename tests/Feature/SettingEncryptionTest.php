<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use Tests\TestCase;

/**
 * Pins the encrypted-settings contract across all three cache layers:
 *  - set() encrypts ENCRYPTED_KEYS at rest (ciphertext in DB)
 *  - get() decrypts transparently
 *  - preload() (run at boot) seeds the memory cache with DECRYPTED values,
 *    so a post-preload get() returns plaintext rather than ciphertext.
 *
 * The preload regression is the important one: get() short-circuits on the
 * in-memory layer without re-running decrypt, so if preload seeds raw
 * ciphertext every Agora/Paymob/Stripe/Reverb secret read would silently
 * return ciphertext and break payments, video, and broadcasting.
 */
class SettingEncryptionTest extends TestCase
{
    use RefreshDatabase;

    /** Reset the static in-memory cache so each test simulates a fresh boot. */
    private function flushMemoryCache(): void
    {
        $prop = (new ReflectionClass(Setting::class))->getProperty('memoryCache');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
        Cache::flush();
    }

    public function test_encrypted_key_is_stored_as_ciphertext_and_read_back_plain(): void
    {
        Setting::set('stripe_secret_key', 'sk_live_ABC123');

        $raw = DB::table('settings')->where('key', 'stripe_secret_key')->value('value');
        $this->assertNotSame('sk_live_ABC123', $raw, 'value must be encrypted at rest');
        $this->assertSame('sk_live_ABC123', Crypt::decryptString($raw));

        $this->flushMemoryCache();
        $this->assertSame('sk_live_ABC123', Setting::get('stripe_secret_key'));
    }

    public function test_preload_seeds_decrypted_secrets_into_memory_cache(): void
    {
        Setting::set('agora_app_id', 'agora-id-xyz');
        Setting::set('paymob_api_key', 'pmb-key-789');
        Setting::set('clinic_name', 'Doctorato'); // non-encrypted control

        // Simulate a fresh request: empty memory cache, then boot-time preload.
        $this->flushMemoryCache();
        Setting::preload();

        // These reads hit Layer 1 (memory) seeded by preload — must be plaintext.
        $this->assertSame('agora-id-xyz', Setting::get('agora_app_id'));
        $this->assertSame('pmb-key-789', Setting::get('paymob_api_key'));
        $this->assertSame('Doctorato', Setting::get('clinic_name'));
    }

    public function test_legacy_plaintext_secret_survives_preload(): void
    {
        // A secret written before encryption existed: stored raw, not via set().
        DB::table('settings')->insert([
            'key' => 'reverb_app_key',
            'value' => 'legacy-plain-key',
            'group' => 'general',
        ]);

        $this->flushMemoryCache();
        Setting::preload();

        // decryptString() throws on plain text; preload must swallow and keep as-is.
        $this->assertSame('legacy-plain-key', Setting::get('reverb_app_key'));
    }
}
