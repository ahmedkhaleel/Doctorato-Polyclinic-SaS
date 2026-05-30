<?php

namespace Tests\Feature\Obgyn;

use App\Models\AntenatalVisit;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ObgynAutomationTest extends TestCase
{
    use RefreshDatabase;

    private function female(string $phone = '0100'): Patient
    {
        return Patient::create(['full_name' => 'Mom', 'phone' => $phone, 'gender' => 'female']);
    }

    #[Test]
    public function integrity_check_flags_delivered_pregnancy_without_record(): void
    {
        $p = $this->female();
        Pregnancy::create(['patient_id' => $p->id, 'status' => 'delivered']); // no delivery record

        $exit = Artisan::call('data:integrity-check', ['--json' => true]);
        $out = Artisan::output();

        $this->assertSame(1, $exit); // non-zero = issues found
        $this->assertStringContainsString('pregnancy_delivered_without_record', $out);
    }

    #[Test]
    public function integrity_check_flags_pregnancy_on_non_female_patient(): void
    {
        $male = Patient::create(['full_name' => 'X', 'phone' => '0199', 'gender' => 'male']);
        Pregnancy::create(['patient_id' => $male->id, 'status' => 'active']);

        Artisan::call('data:integrity-check', ['--json' => true]);
        $this->assertStringContainsString('pregnancy_non_female_patient', Artisan::output());
    }

    #[Test]
    public function anc_reminder_dry_run_lists_visits_due_in_window(): void
    {
        Setting::set('sms_enabled', '1');

        $p = $this->female('01099999999');
        $pregnancy = Pregnancy::create(['patient_id' => $p->id, 'lmp' => now()->subWeeks(20)->toDateString(), 'status' => 'active']);
        // anc_reminder_days defaults to 2 → next visit two days out.
        AntenatalVisit::create([
            'pregnancy_id' => $pregnancy->id,
            'visit_date' => now()->toDateString(),
            'next_visit_date' => now()->addDays(2)->toDateString(),
        ]);

        Artisan::call('obgyn:reminders', ['--type' => 'anc', '--dry-run' => true]);
        $out = Artisan::output();

        $this->assertStringContainsString('01099999999', $out);
        $this->assertStringContainsString('Would send 1', $out);
    }

    #[Test]
    public function reminders_skip_when_sms_disabled(): void
    {
        Setting::set('sms_enabled', '0');

        Artisan::call('obgyn:reminders', ['--type' => 'edd']);
        $this->assertStringContainsString('SMS is not enabled', Artisan::output());
    }
}
