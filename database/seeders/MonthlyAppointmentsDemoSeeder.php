<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Fills the CURRENT month with appointments for every active doctor so the
 * calendars, queues and dashboards look busy in the demo. Three states:
 *   • past days   → completed visits (with invoice + payment + commission)
 *   • today       → in_progress + waiting (the chair-side queue)
 *   • future days → confirmed bookings, not yet a visit (upcoming / not done)
 *
 * Idempotent: a doctor is skipped if this seeder already filled the month
 * (bookings tagged source='monthly_demo'). Demo/staging ONLY — never auto-deployed.
 *
 *   php artisan db:seed --class=Database\\Seeders\\MonthlyAppointmentsDemoSeeder
 */
class MonthlyAppointmentsDemoSeeder extends Seeder
{
    private const FEE = [
        'dental' => 300, 'derma' => 400, 'pediatric' => 250, 'obgyn' => 350,
        'psychiatry' => 350, 'neurology' => 350, 'physiotherapy' => 250,
    ];

    private const BOOKING_TYPE = [
        'dental' => 'dental_consultation', 'derma' => 'dermatology_consultation',
        'pediatric' => 'pediatric_consultation', 'obgyn' => 'obgyn_consultation',
        'psychiatry' => 'psychiatry_consultation', 'neurology' => 'neurology_consultation',
        'physiotherapy' => 'physiotherapy_consultation',
    ];

    public function run(): void
    {
        $payMethodId = PaymentMethod::firstOrCreate(['name_en' => 'Cash'], ['name_ar' => 'نقدي', 'is_active' => true])->id;
        $createdBy = User::whereHas('role', fn ($q) => $q->whereIn('name', ['super_admin', 'admin']))->value('id');
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();
        $today = Carbon::today();

        $doctors = Doctor::where('status', 'active')->get();
        if ($doctors->isEmpty()) {
            $this->command?->warn('No active doctors — nothing to seed.');

            return;
        }

        foreach ($doctors as $doctor) {
            try {
                DB::transaction(fn () => $this->seedDoctorMonth($doctor, $start, $end, $today, $payMethodId, $createdBy));
            } catch (\Throwable $e) {
                if (app()->runningUnitTests()) {
                    throw $e;
                }
                $this->command?->warn("  ✗ {$doctor->name_en} skipped: ".$e->getMessage());
            }
        }

        $this->command?->info('Monthly demo appointments seeded for '.$doctors->count().' doctors.');
    }

    private function seedDoctorMonth(Doctor $doctor, Carbon $start, Carbon $end, Carbon $today, int $payMethodId, ?int $createdBy): void
    {
        $module = $doctor->module ?: 'derma';

        // Idempotent: already filled this month?
        if (Booking::where('doctor_id', $doctor->id)->where('source', 'monthly_demo')
            ->whereBetween('preferred_date', [$start->toDateString(), $end->toDateString()])->exists()) {
            return;
        }

        // Reuse the module's patients; fall back to any active patient, else create a few.
        $patients = Patient::where('is_active', true)->inRandomOrder()->limit(10)->get();
        if ($patients->isEmpty()) {
            $patients = $this->makePatients();
        }

        $fee = (float) (self::FEE[$module] ?? 300);
        $rate = (float) ($doctor->default_commission_percentage ?: 25);
        $bookingType = self::BOOKING_TYPE[$module] ?? 'service';
        $n = 0;

        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            if ($day->isFriday()) {
                continue; // clinic closed
            }
            // ~ every other working day, to keep volume sensible.
            if ($day->day % 2 === 0) {
                continue;
            }

            $slots = $day->isSameDay($today) ? 3 : 2; // a fuller queue today
            for ($i = 0; $i < $slots; $i++) {
                $patient = $patients[$n % $patients->count()];
                $n++;
                $time = sprintf('%02d:%02d', 9 + $i * 2, 0);
                [$bookingStatus, $visitStatus] = $this->statusFor($day, $today, $i);

                $booking = Booking::create([
                    'booking_number' => Booking::generateBookingNumber(),
                    'source' => 'monthly_demo', 'module' => $module, 'booking_type' => $bookingType,
                    'full_name' => $patient->full_name, 'phone' => $patient->phone, 'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id, 'preferred_date' => $day->toDateString(), 'preferred_time' => $time,
                    'status' => $bookingStatus,
                ]);

                // Future bookings stay as confirmed appointments (no visit yet).
                if ($visitStatus === null) {
                    continue;
                }

                $visit = Visit::create([
                    'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'booking_id' => $booking->id,
                    'module' => $module, 'visit_type' => 'consultation', 'status' => $visitStatus,
                    'visit_date' => $day->toDateString(), 'scheduled_time' => $time,
                    'diagnosis' => $visitStatus === 'completed' ? 'كشف تجريبي / Demo visit' : null,
                    'started_at' => in_array($visitStatus, ['in_progress', 'completed'], true) ? $day->copy()->setTimeFromTimeString($time) : null,
                    'completed_at' => $visitStatus === 'completed' ? $day->copy()->setTimeFromTimeString($time)->addMinutes(20) : null,
                ]);

                if ($visitStatus === 'completed') {
                    DB::table('visits')->where('id', $visit->id)->update([
                        'commission_rate' => $rate, 'commission_amount' => round($fee * $rate / 100, 2),
                    ]);
                    $this->billCompleted($visit, $patient, $module, $fee, $day, $payMethodId, $createdBy);
                }
            }
        }
    }

    /** @return array{0:string,1:?string} [bookingStatus, visitStatus|null] */
    private function statusFor(Carbon $day, Carbon $today, int $slot): array
    {
        if ($day->lt($today)) {
            return ['completed', 'completed'];
        }
        if ($day->isSameDay($today)) {
            // First slot in progress, the rest waiting.
            return $slot === 0 ? ['confirmed', 'in_progress'] : ['confirmed', 'waiting'];
        }

        return ['confirmed', null]; // future: appointment only, not executed yet
    }

    private function billCompleted(Visit $visit, Patient $patient, string $module, float $fee, Carbon $day, int $payMethodId, ?int $createdBy): void
    {
        $invoice = Invoice::create([
            'invoice_number' => Invoice::generateInvoiceNumber(),
            'invoice_date' => $day->toDateString(), 'patient_id' => $patient->id, 'visit_id' => $visit->id,
            'subtotal' => $fee, 'discount_amount' => 0, 'tax_amount' => 0, 'total' => $fee,
            'module' => $module, 'created_by' => $createdBy,
        ]);

        Payment::create([
            'invoice_id' => $invoice->id, 'patient_id' => $patient->id, 'payment_method_id' => $payMethodId,
            'amount' => $fee, 'payment_date' => $day->toDateString(), 'reference_number' => 'MDEMO-'.$invoice->id,
        ]);

        if (method_exists($invoice, 'recalculateStatus')) {
            $invoice->recalculateStatus();
        }
    }

    /** @return \Illuminate\Support\Collection<int,Patient> */
    private function makePatients()
    {
        $names = ['أحمد علي', 'سارة محمد', 'خالد حسن', 'منى سمير', 'يوسف كريم', 'دانة فهد'];
        $out = collect();
        foreach ($names as $idx => $name) {
            $p = Patient::firstOrNew(['phone' => '0590000'.str_pad((string) $idx, 3, '0', STR_PAD_LEFT)]);
            $p->fill(['full_name' => $name, 'gender' => $idx % 2 ? 'female' : 'male', 'nationality' => 'Saudi']);
            $p->save();
            $p->forceFill(['is_active' => true])->saveQuietly();
            $out->push($p);
        }

        return $out;
    }
}
