<?php

namespace Tests\Feature\Patient;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Patient notification preferences flow:
 *  - Default values when patient is fresh
 *  - wantsNotification helper for all 6 combos
 *  - POST /profile/preferences updates correctly
 *  - Unchecked checkbox = false (browsers omit them)
 *  - Marketing SMS legacy default is FALSE (not true)
 */
class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function makePatient(): Patient
    {
        $role = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]
        );

        $user = User::create([
            'name' => 'Test', 'email' => 'test-prefs-'.random_int(1000, 9999).'@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        // user_id + is_active are guarded against mass assignment — set directly.
        $patient = Patient::create(['full_name' => 'Test Patient', 'phone' => '+971500000000']);
        $patient->forceFill(['user_id' => $user->id, 'is_active' => true])->save();

        return $patient;
    }

    public function test_fresh_patient_defaults_to_opt_in_for_transactional_email(): void
    {
        $p = $this->makePatient();

        $this->assertTrue($p->wantsNotification('bookings', 'email'));
        $this->assertTrue($p->wantsNotification('bookings', 'sms'));
        $this->assertTrue($p->wantsNotification('reminders', 'email'));
        $this->assertTrue($p->wantsNotification('reminders', 'sms'));
        $this->assertTrue($p->wantsNotification('marketing', 'email'));
    }

    public function test_marketing_sms_is_opt_in_by_default(): void
    {
        $p = $this->makePatient();

        // Refresh to get the actual DB column value (default false)
        $p->refresh();
        $this->assertFalse($p->wantsNotification('marketing', 'sms'));
    }

    public function test_wants_notification_with_unknown_category_returns_false(): void
    {
        $p = $this->makePatient();
        $this->assertFalse($p->wantsNotification('garbage', 'email'));
        $this->assertFalse($p->wantsNotification('bookings', 'fax'));
    }

    public function test_post_preferences_updates_db(): void
    {
        $p = $this->makePatient();

        $resp = $this->actingAs($p->user)->post('/ar/patient/profile/preferences', [
            'preferred_language' => 'en',
            'notify_email_bookings' => '1',
            'notify_email_reminders' => '1',
            'notify_email_marketing' => '0',  // explicit opt-out
            // notify_sms_* intentionally omitted (browsers do this for unchecked)
        ]);

        $resp->assertRedirect();
        $p->refresh();

        $this->assertSame('en', $p->preferred_language);
        $this->assertTrue((bool) $p->notify_email_bookings);
        $this->assertTrue((bool) $p->notify_email_reminders);
        $this->assertFalse((bool) $p->notify_email_marketing);
        $this->assertFalse((bool) $p->notify_sms_bookings);    // omitted → false
        $this->assertFalse((bool) $p->notify_sms_reminders);   // omitted → false
        $this->assertFalse((bool) $p->notify_sms_marketing);   // omitted → false
    }

    public function test_post_preferences_requires_auth(): void
    {
        $this->post('/ar/patient/profile/preferences', ['preferred_language' => 'en'])
            ->assertRedirect();  // patient.auth middleware → /patient/login
    }
}
