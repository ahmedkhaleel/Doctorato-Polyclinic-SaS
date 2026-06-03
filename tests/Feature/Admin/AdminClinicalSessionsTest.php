<?php

namespace Tests\Feature\Admin;

use App\Models\CosmeticConsent;
use App\Models\CosmeticConsentTemplate;
use App\Models\CosmeticProcedure;
use App\Models\CosmeticSession;
use App\Models\DermaSession;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\Supply;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers the high-risk clinical-event write paths for Derma and Cosmetic
 * sessions: creation, completion-driven invoicing, inventory consumption,
 * billing reversal on un-complete, and the signed-consent gate. These
 * endpoints have financial + stock side-effects, so each is asserted at the
 * DB level (invoice lines, supply transactions, stock deltas).
 */
class AdminClinicalSessionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Patient $patient;

    private Doctor $doctor;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::firstOrCreate(
            ['name' => 'admin'],
            ['display_name_en' => 'Admin', 'display_name_ar' => 'مدير', 'permissions' => ['*'], 'is_system' => true]
        );

        $this->admin = User::create([
            'name' => 'Admin', 'email' => 'admin-sessions@test.com',
            'password' => bcrypt('password'), 'role_id' => $role->id, 'is_active' => true,
        ]);

        // Cosmetic + Derma sessions both live behind the derma module gate.
        DB::table('module_settings')->updateOrInsert(
            ['module' => 'derma', 'key' => 'enabled'],
            ['value' => '1', 'created_at' => now(), 'updated_at' => now()]
        );
        cache()->forget('module_derma_enabled');

        $this->patient = new Patient([
            'full_name' => 'Session Patient', 'phone' => '0500009001', 'gender' => 'female',
        ]);
        $this->patient->file_number = 'PAT-SES-001';
        $this->patient->is_active = true;
        $this->patient->save();

        $this->doctor = Doctor::create([
            'name_ar' => 'دكتور جلدية', 'name_en' => 'Derma Doctor',
            'specialization_ar' => 'جلدية', 'specialization_en' => 'Derma',
            'department' => 'derma', 'status' => 'active',
        ]);
    }

    // ─── Derma sessions ─────────────────────────────────────────

    public function test_derma_sessions_index_renders(): void
    {
        $this->actingAs($this->admin)->get('/admin/derma/sessions')->assertOk();
    }

    public function test_create_derma_session_persists_row(): void
    {
        $this->actingAs($this->admin)->post('/admin/derma/sessions', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'session_type' => 'laser',
            'area_treated' => 'Face',
            'cost' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('derma_sessions', [
            'patient_id' => $this->patient->id,
            'session_type' => 'laser',
            'area_treated' => 'Face',
        ]);
    }

    public function test_completed_derma_session_with_cost_generates_invoice_line(): void
    {
        $this->actingAs($this->admin)->post('/admin/derma/sessions', [
            'patient_id' => $this->patient->id,
            'session_type' => 'peel',
            'cost' => 750,
            'completed_at' => now()->toDateTimeString(),
        ])->assertRedirect();

        $session = DermaSession::where('patient_id', $this->patient->id)->firstOrFail();

        // Session is linked to a freshly created invoice + line for its cost.
        $this->assertNotNull($session->invoice_id, 'completed billable session should be invoiced');
        $this->assertNotNull($session->invoice_item_id);
        $this->assertDatabaseHas('invoice_items', [
            'id' => $session->invoice_item_id,
            'invoice_id' => $session->invoice_id,
            'total' => 750,
        ]);
    }

    public function test_uncompleting_derma_session_reverses_billing(): void
    {
        // Create completed + billed.
        $this->actingAs($this->admin)->post('/admin/derma/sessions', [
            'patient_id' => $this->patient->id,
            'session_type' => 'laser',
            'cost' => 400,
            'completed_at' => now()->toDateTimeString(),
        ])->assertRedirect();

        $session = DermaSession::where('patient_id', $this->patient->id)->firstOrFail();
        $itemId = $session->invoice_item_id;
        $this->assertNotNull($itemId);

        // Update it back to NOT completed → billing must be reversed.
        $this->actingAs($this->admin)->post("/admin/derma/sessions/{$session->id}", [
            'patient_id' => $this->patient->id,
            'session_type' => 'laser',
            'cost' => 400,
            'completed_at' => null,
        ])->assertRedirect();

        $session->refresh();
        $this->assertNull($session->invoice_id, 'un-completed session should drop its invoice link');
        $this->assertNull($session->invoice_item_id);
        $this->assertDatabaseMissing('invoice_items', ['id' => $itemId]);
    }

    // ─── Cosmetic sessions ──────────────────────────────────────

    public function test_create_cosmetic_session_persists_row(): void
    {
        $procedure = $this->makeProcedure();

        $this->actingAs($this->admin)->post('/admin/cosmetic/sessions', [
            'patient_id' => $this->patient->id,
            'doctor_id' => $this->doctor->id,
            'procedure_id' => $procedure->id,
            'cost' => 0,
        ])->assertRedirect();

        $this->assertDatabaseHas('cosmetic_sessions', [
            'patient_id' => $this->patient->id,
            'procedure_id' => $procedure->id,
        ]);
    }

    public function test_completed_cosmetic_session_bills_and_draws_inventory(): void
    {
        $supply = Supply::create([
            'name_ar' => 'فيلر', 'name_en' => 'Filler', 'unit' => 'syringe',
            'quantity' => 10, 'min_quantity' => 1, 'purchase_price' => 100, 'is_active' => true,
        ]);
        $procedure = $this->makeProcedure(['supply_id' => $supply->id, 'default_consumption_qty' => 2]);

        $this->actingAs($this->admin)->post('/admin/cosmetic/sessions', [
            'patient_id' => $this->patient->id,
            'procedure_id' => $procedure->id,
            'area_treated' => 'Lips',
            'cost' => 1200,
            'completed_at' => now()->toDateTimeString(),
        ])->assertRedirect();

        $session = CosmeticSession::where('patient_id', $this->patient->id)->firstOrFail();

        // Billed.
        $this->assertNotNull($session->invoice_item_id);
        $this->assertDatabaseHas('invoice_items', [
            'id' => $session->invoice_item_id, 'total' => 1200,
        ]);

        // Inventory drawn: usage transaction + stock decremented 10 → 8.
        $this->assertNotNull($session->supply_transaction_id);
        $this->assertDatabaseHas('supply_transactions', [
            'id' => $session->supply_transaction_id,
            'supply_id' => $supply->id,
            'transaction_type' => 'usage',
            'quantity' => 2,
        ]);
        $this->assertEquals(8.0, (float) $supply->fresh()->quantity);
    }

    public function test_completed_cosmetic_session_blocked_without_signed_consent(): void
    {
        $procedure = $this->makeProcedure();

        // An active template flags this procedure as requiring a signed consent.
        CosmeticConsentTemplate::create([
            'procedure_id' => $procedure->id,
            'title_ar' => 'إقرار', 'title_en' => 'Consent',
            'body_ar' => 'نص الإقرار', 'body_en' => 'Consent body',
            'requires_signature' => true, 'is_active' => true,
        ]);

        // Completing the session with no signed consent on file → blocked.
        $this->actingAs($this->admin)
            ->from('/admin/cosmetic/sessions')
            ->post('/admin/cosmetic/sessions', [
                'patient_id' => $this->patient->id,
                'procedure_id' => $procedure->id,
                'cost' => 500,
                'completed_at' => now()->toDateTimeString(),
            ])
            ->assertSessionHasErrors('consent');

        $this->assertDatabaseMissing('cosmetic_sessions', [
            'patient_id' => $this->patient->id,
            'procedure_id' => $procedure->id,
        ]);
    }

    public function test_completed_cosmetic_session_allowed_with_signed_consent(): void
    {
        $procedure = $this->makeProcedure();

        CosmeticConsentTemplate::create([
            'procedure_id' => $procedure->id,
            'title_ar' => 'إقرار', 'title_en' => 'Consent',
            'body_ar' => 'نص الإقرار', 'body_en' => 'Consent body',
            'requires_signature' => true, 'is_active' => true,
        ]);

        // A signed consent on file for this patient + procedure clears the gate.
        CosmeticConsent::create([
            'patient_id' => $this->patient->id,
            'procedure_id' => $procedure->id,
            'consent_text' => 'I agree',
            'signed_at' => now(),
        ]);

        $this->actingAs($this->admin)->post('/admin/cosmetic/sessions', [
            'patient_id' => $this->patient->id,
            'procedure_id' => $procedure->id,
            'cost' => 500,
            'completed_at' => now()->toDateTimeString(),
        ])->assertRedirect();

        $this->assertDatabaseHas('cosmetic_sessions', [
            'patient_id' => $this->patient->id,
            'procedure_id' => $procedure->id,
        ]);
    }

    private function makeProcedure(array $overrides = []): CosmeticProcedure
    {
        return CosmeticProcedure::create(array_merge([
            'name_ar' => 'بوتوكس', 'name_en' => 'Botox', 'category' => 'injectable',
            'default_price' => 500, 'is_active' => true,
        ], $overrides));
    }
}
