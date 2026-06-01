<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Creates the four sales-demo accounts (admin / doctor / secretary / patient).
 * They are flagged is_demo, so DemoModeGuard blocks deletes + core-settings.
 * The demo admin role grants every view/create/update permission EXCEPT delete
 * and core-settings, so the UI itself reflects what they can do.
 *
 * Idempotent. Password comes from env DEMO_PASSWORD (default below).
 */
class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('DEMO_PASSWORD', 'DemoClinic@2026');

        // ── Demo admin role (named 'admin' to pass AdminAuth whitelist) ──
        $adminRole = Role::updateOrCreate(
            ['name' => 'admin'],
            [
                'display_name_en' => 'Demo Admin',
                'display_name_ar' => 'مدير تجريبي',
                'permissions' => $this->demoAdminPermissions(),
                'is_system' => false,
            ]
        );

        $admin = $this->makeUser('demo.admin@doctorato.net', 'Demo Admin / مدير تجريبي', $adminRole->id, $password);

        // ── Demo doctor → link to a populated demo Doctor ──
        $doctorRole = Role::firstOrCreate(
            ['name' => 'doctor'],
            ['display_name_en' => 'Doctor', 'display_name_ar' => 'طبيب', 'permissions' => ['ai.view', 'ai.doctor'], 'is_system' => true]
        );
        $doctorUser = $this->makeUser('demo.doctor@doctorato.net', 'Demo Doctor / طبيب تجريبي', $doctorRole->id, $password);
        $this->attachDoctor($doctorUser);

        // ── Demo secretary ──
        $secretaryRole = Role::firstOrCreate(
            ['name' => 'secretary'],
            ['display_name_en' => 'Secretary', 'display_name_ar' => 'سكرتير', 'permissions' => [], 'is_system' => true]
        );
        $this->makeUser('demo.secretary@doctorato.net', 'Demo Secretary / سكرتارية تجريبية', $secretaryRole->id, $password);

        // ── Demo patient → link to a populated demo Patient (website portal) ──
        $patientRole = Role::firstOrCreate(
            ['name' => 'patient'],
            ['display_name_en' => 'Patient', 'display_name_ar' => 'مريض', 'permissions' => [], 'is_system' => true]
        );
        $patientUser = $this->makeUser('demo.patient@doctorato.net', 'Demo Patient / مريض تجريبي', $patientRole->id, $password);
        $this->attachPatient($patientUser);

        $this->command->info('Demo users ready (password from DEMO_PASSWORD, default DemoClinic@2026):');
        foreach (['demo.admin', 'demo.doctor', 'demo.secretary', 'demo.patient'] as $u) {
            $this->command->info("  {$u}@doctorato.net");
        }
    }

    private function makeUser(string $email, string $name, int $roleId, string $password): User
    {
        $user = User::withTrashed()->firstOrNew(['email' => $email]);
        $user->fill([
            'name' => $name,
            'role_id' => $roleId,
            'is_active' => true,
            'is_demo' => true,
            'password' => Hash::make($password),
        ]);
        if (method_exists($user, 'restore') && $user->trashed()) {
            $user->restore();
        }
        $user->save();

        return $user;
    }

    private function attachDoctor(User $user): void
    {
        // Dedicated demo doctor so the panel data is clearly "this doctor's".
        $module = 'derma';
        $doctor = Doctor::firstOrNew(['user_id' => $user->id]);
        $doctor->fill([
            'name_ar' => 'د. سامي الديمو',
            'name_en' => 'Dr. Sami Demo',
            'specialization_ar' => 'جلدية وتجميل',
            'specialization_en' => 'Dermatology & Cosmetics',
            'status' => 'active',
            'user_id' => $user->id,
        ]);
        if (DB::getSchemaBuilder()->hasColumn('doctors', 'module')) {
            $doctor->module = $module;
        }
        $doctor->save();

        $this->seedDoctorHistory($doctor, $module);
    }

    /** Builds the demo doctor's own patients/queue/visits/prescriptions. */
    private function seedDoctorHistory(Doctor $doctor, string $module): void
    {
        // Idempotent: only build once (6 appointments are always created, even
        // though the future one has no visit yet).
        if (DB::table('booking_appointments')->where('doctor_id', $doctor->id)->count() >= 6) {
            return;
        }

        $now = now();
        $branchId = 1;
        $hasBranch = fn (string $t) => DB::getSchemaBuilder()->hasColumn($t, 'branch_id');

        $service = DB::table('services')->whereNotNull('price')->where('price', '>', 0)->first();
        $pmId = DB::table('payment_methods')->value('id');
        $adminId = User::whereHas('role', fn ($q) => $q->whereIn('name', ['super_admin', 'admin']))->value('id');
        // Reuse existing demo patients (exclude none); fall back to the linked demo patient.
        $patients = Patient::orderBy('id')->limit(6)->get();

        if (! $service || $patients->isEmpty()) {
            return;
        }

        // i => [visit_date, status, started] — mix of today's queue + history.
        $plan = [
            ['date' => $now->copy(), 'status' => 'waiting', 'started' => false, 'dx' => null],
            ['date' => $now->copy(), 'status' => 'in_progress', 'started' => true, 'dx' => 'كشف متابعة'],
            ['date' => $now->copy()->subDays(3), 'status' => 'completed', 'started' => true, 'dx' => 'حب شباب'],
            ['date' => $now->copy()->subDays(9), 'status' => 'completed', 'started' => true, 'dx' => 'تصبغات'],
            ['date' => $now->copy()->subDays(16), 'status' => 'completed', 'started' => true, 'dx' => 'إكزيما'],
            ['date' => $now->copy()->addDays(2), 'status' => 'scheduled', 'started' => false, 'dx' => null],
        ];

        foreach ($plan as $i => $p) {
            $patient = $patients[$i % $patients->count()];
            $price = (float) $service->price;
            $date = $p['date'];

            $bookingId = DB::table('bookings')->insertGetId(array_filter([
                'branch_id' => $hasBranch('bookings') ? $branchId : null,
                'patient_id' => $patient->id, 'booking_number' => \App\Models\Booking::generateBookingNumber(),
                'source' => 'clinic', 'module' => $module, 'booking_type' => $module.'_service',
                'full_name' => $patient->full_name, 'phone' => $patient->phone,
                'status' => $p['status'] === 'completed' ? 'completed' : 'confirmed',
                'created_at' => $date, 'updated_at' => $date,
            ], fn ($v) => $v !== null));

            $bsId = DB::table('booking_services')->insertGetId(array_filter([
                'branch_id' => $hasBranch('booking_services') ? $branchId : null,
                'booking_id' => $bookingId, 'service_id' => $service->id, 'doctor_id' => $doctor->id,
                'sessions_count' => 1, 'unit_price' => $price, 'total_price' => $price,
                'status' => $p['status'] === 'completed' ? 'completed' : 'in_progress',
                'created_at' => $date, 'updated_at' => $date,
            ], fn ($v) => $v !== null));

            DB::table('booking_appointments')->insert(array_filter([
                'branch_id' => $hasBranch('booking_appointments') ? $branchId : null,
                'booking_id' => $bookingId, 'booking_service_id' => $bsId, 'doctor_id' => $doctor->id,
                'appointment_date' => $date->toDateString(), 'start_time' => sprintf('%02d:00', 9 + $i),
                'end_time' => sprintf('%02d:30', 9 + $i), 'session_number' => 1,
                'status' => $p['status'] === 'completed' ? 'completed' : 'scheduled',
                'created_at' => $date, 'updated_at' => $date,
            ], fn ($v) => $v !== null));

            // A future appointment has no visit yet (created at check-in). The
            // visits.status enum has no 'scheduled' value, so skip the visit here.
            if ($p['status'] === 'scheduled') {
                continue;
            }

            $visitId = DB::table('visits')->insertGetId(array_filter([
                'branch_id' => $hasBranch('visits') ? $branchId : null,
                'patient_id' => $patient->id, 'booking_id' => $bookingId, 'doctor_id' => $doctor->id,
                'service_id' => $service->id, 'visit_type' => 'session', 'module' => $module,
                'status' => $p['status'], 'diagnosis' => $p['dx'],
                'doctor_notes' => $p['status'] === 'completed' ? 'تم العلاج والمتابعة.' : null,
                'visit_date' => $date->toDateString(),
                'started_at' => $p['started'] ? $date->copy()->setTime(9 + $i, 0) : null,
                'completed_at' => $p['status'] === 'completed' ? $date->copy()->setTime(9 + $i, 30) : null,
                'created_at' => $date, 'updated_at' => $date,
            ], fn ($v) => $v !== null));

            if ($p['status'] !== 'completed') {
                continue;
            }

            // Completed visits get a prescription + invoice + payment.
            $rxId = DB::table('prescriptions')->insertGetId(array_filter([
                'branch_id' => $hasBranch('prescriptions') ? $branchId : null,
                'patient_id' => $patient->id, 'visit_id' => $visitId, 'doctor_id' => $doctor->id,
                'diagnosis' => $p['dx'], 'created_at' => $date, 'updated_at' => $date,
            ], fn ($v) => $v !== null));
            DB::table('prescription_items')->insert(array_filter([
                'branch_id' => $hasBranch('prescription_items') ? $branchId : null,
                'prescription_id' => $rxId, 'medication_name' => 'Topical Cream', 'dosage' => '-',
                'frequency' => 'مرتين يومياً', 'duration' => '4 أسابيع', 'sort_order' => 0,
                'created_at' => $date, 'updated_at' => $date,
            ], fn ($v) => $v !== null));

            $invId = DB::table('invoices')->insertGetId(array_filter([
                'branch_id' => $hasBranch('invoices') ? $branchId : null,
                'invoice_number' => \App\Models\Invoice::generateInvoiceNumber(),
                'invoice_date' => $date->toDateString(), 'patient_id' => $patient->id, 'visit_id' => $visitId,
                'booking_id' => $bookingId, 'subtotal' => $price, 'discount_amount' => 0, 'tax_amount' => 0,
                'total' => $price, 'paid_amount' => $price, 'status' => 'paid', 'module' => $module,
                'created_by' => $adminId, 'created_at' => $date, 'updated_at' => $date,
            ], fn ($v) => $v !== null));
            DB::table('invoice_items')->insert([
                'invoice_id' => $invId, 'description_ar' => $p['dx'], 'description_en' => 'Session',
                'quantity' => 1, 'unit_price' => $price, 'discount' => 0, 'total' => $price,
                'created_at' => $date, 'updated_at' => $date,
            ]);
            if ($pmId) {
                DB::table('payments')->insert(array_filter([
                    'branch_id' => $hasBranch('payments') ? $branchId : null,
                    'invoice_id' => $invId, 'patient_id' => $patient->id, 'payment_method_id' => $pmId,
                    'amount' => $price, 'payment_date' => $date->toDateString(), 'received_by' => $adminId,
                    'created_at' => $date, 'updated_at' => $date,
                ], fn ($v) => $v !== null));
            }
        }
    }

    private function attachPatient(User $user): void
    {
        // Free the demo user from any previously-linked patient first
        // (patients.user_id is unique — an earlier run may have linked another row).
        Patient::withTrashed()->where('user_id', $user->id)->where('phone', '!=', '01000000099')
            ->update(['user_id' => null]);

        // Dedicated demo patient with a full medical history under their own name,
        // so the portal dashboard is populated (not empty). Keyed on a fixed phone.
        $patient = Patient::withTrashed()->firstOrNew(['phone' => '01000000099']);
        $patient->fill([
            'full_name' => 'محمد الديمو / Demo Patient',
            'gender' => 'male',
            'date_of_birth' => '1990-05-15',
            'blood_type' => 'O+',
            'allergies' => 'Penicillin',
            'chronic_conditions' => 'None',
            'address' => 'القاهرة، مصر',
            'is_active' => true,
            'user_id' => $user->id,
        ]);
        if (! $patient->file_number) {
            $patient->file_number = Patient::generateFileNumber();
        }
        if (method_exists($patient, 'trashed') && $patient->trashed()) {
            $patient->restore();
        }
        $patient->save();

        $this->seedPatientHistory($patient);
    }

    /** Builds bookings + visits + prescriptions + invoices + payments for the demo patient. */
    private function seedPatientHistory(Patient $patient): void
    {
        // Idempotent: only build once.
        if (DB::table('bookings')->where('patient_id', $patient->id)->count() >= 4) {
            return;
        }

        $now = now();
        $branchId = 1;
        $hasBranch = fn (string $t) => DB::getSchemaBuilder()->hasColumn($t, 'branch_id');

        $doctor = Doctor::where('status', 'active')->first() ?? Doctor::first();
        $services = DB::table('services')->whereNotNull('price')->where('price', '>', 0)->limit(4)->get();
        $pmId = DB::table('payment_methods')->value('id');
        $adminId = User::whereHas('role', fn ($q) => $q->whereIn('name', ['super_admin', 'admin']))->value('id');

        if (! $doctor || $services->isEmpty()) {
            return; // need reference data
        }

        $cases = [
            ['module' => 'derma', 'dx' => 'حب شباب', 'notes' => 'وصف كريم موضعي ومتابعة شهرية.', 'meds' => [['Isotretinoin', '20mg', 'مرة يومياً', '3 أشهر'], ['Clindamycin', 'جل', 'مرتين يومياً', '8 أسابيع']], 'pay' => 'paid'],
            ['module' => 'derma', 'dx' => 'تصبغات جلدية', 'notes' => 'جلسة ليزر + واقي شمس.', 'meds' => [['Vitamin C Serum', '-', 'مرة يومياً', 'مستمر']], 'pay' => 'partial'],
            ['module' => 'dental', 'dx' => 'تسوّس ضرس', 'notes' => 'تم حشو الضرس بحشوة مركبة.', 'meds' => [['Amoxicillin', '500mg', 'ثلاث مرات يومياً', '7 أيام'], ['Ibuprofen', '400mg', 'عند اللزوم', '5 أيام']], 'pay' => 'paid'],
            ['module' => 'pediatric', 'dx' => 'متابعة نمو', 'notes' => 'النمو ضمن المعدل الطبيعي.', 'meds' => [['Vitamin D3', '1000 IU', 'مرة يومياً', '3 أشهر']], 'pay' => 'unpaid'],
        ];

        foreach ($cases as $i => $c) {
            $service = $services[$i % $services->count()];
            $price = (float) $service->price;
            $date = $now->copy()->subDays(($i + 1) * 9);

            $bookingId = DB::table('bookings')->insertGetId(array_filter([
                'branch_id' => $hasBranch('bookings') ? $branchId : null,
                'patient_id' => $patient->id,
                'booking_number' => \App\Models\Booking::generateBookingNumber(),
                'source' => 'clinic', 'module' => $c['module'], 'booking_type' => $c['module'].'_service',
                'full_name' => $patient->full_name, 'phone' => $patient->phone, 'status' => 'completed',
                'created_at' => $date, 'updated_at' => $date,
            ], fn ($v) => $v !== null));

            $bsId = DB::table('booking_services')->insertGetId(array_filter([
                'branch_id' => $hasBranch('booking_services') ? $branchId : null,
                'booking_id' => $bookingId, 'service_id' => $service->id, 'doctor_id' => $doctor->id,
                'sessions_count' => 1, 'unit_price' => $price, 'total_price' => $price, 'status' => 'completed',
                'created_at' => $date, 'updated_at' => $date,
            ], fn ($v) => $v !== null));

            DB::table('booking_appointments')->insert(array_filter([
                'branch_id' => $hasBranch('booking_appointments') ? $branchId : null,
                'booking_id' => $bookingId, 'booking_service_id' => $bsId, 'doctor_id' => $doctor->id,
                'appointment_date' => $date->toDateString(), 'start_time' => sprintf('%02d:00', 10 + $i),
                'end_time' => sprintf('%02d:30', 10 + $i), 'session_number' => 1, 'status' => 'completed',
                'created_at' => $date, 'updated_at' => $date,
            ], fn ($v) => $v !== null));

            $visitId = DB::table('visits')->insertGetId(array_filter([
                'branch_id' => $hasBranch('visits') ? $branchId : null,
                'patient_id' => $patient->id, 'booking_id' => $bookingId, 'doctor_id' => $doctor->id,
                'service_id' => $service->id, 'visit_type' => 'session', 'module' => $c['module'],
                'status' => 'completed', 'diagnosis' => $c['dx'], 'doctor_notes' => $c['notes'],
                'visit_date' => $date->toDateString(), 'completed_at' => $date,
                'created_at' => $date, 'updated_at' => $date,
            ], fn ($v) => $v !== null));

            $rxId = DB::table('prescriptions')->insertGetId(array_filter([
                'branch_id' => $hasBranch('prescriptions') ? $branchId : null,
                'patient_id' => $patient->id, 'visit_id' => $visitId, 'doctor_id' => $doctor->id,
                'diagnosis' => $c['dx'], 'created_at' => $date, 'updated_at' => $date,
            ], fn ($v) => $v !== null));

            foreach ($c['meds'] as $j => $m) {
                DB::table('prescription_items')->insert(array_filter([
                    'branch_id' => $hasBranch('prescription_items') ? $branchId : null,
                    'prescription_id' => $rxId, 'medication_name' => $m[0], 'dosage' => $m[1],
                    'frequency' => $m[2], 'duration' => $m[3], 'sort_order' => $j,
                    'created_at' => $date, 'updated_at' => $date,
                ], fn ($v) => $v !== null));
            }

            $paid = $c['pay'] === 'paid' ? $price : ($c['pay'] === 'partial' ? round($price / 2, 2) : 0);
            $status = $c['pay'];
            $invId = DB::table('invoices')->insertGetId(array_filter([
                'branch_id' => $hasBranch('invoices') ? $branchId : null,
                'invoice_number' => \App\Models\Invoice::generateInvoiceNumber(),
                'invoice_date' => $date->toDateString(), 'patient_id' => $patient->id, 'visit_id' => $visitId,
                'booking_id' => $bookingId, 'subtotal' => $price, 'discount_amount' => 0, 'tax_amount' => 0,
                'total' => $price, 'paid_amount' => $paid, 'status' => $status, 'module' => $c['module'],
                'created_by' => $adminId, 'created_at' => $date, 'updated_at' => $date,
            ], fn ($v) => $v !== null));

            DB::table('invoice_items')->insert([
                'invoice_id' => $invId, 'description_ar' => $c['dx'], 'description_en' => 'Service session',
                'quantity' => 1, 'unit_price' => $price, 'discount' => 0, 'total' => $price,
                'created_at' => $date, 'updated_at' => $date,
            ]);

            if ($paid > 0 && $pmId) {
                DB::table('payments')->insert(array_filter([
                    'branch_id' => $hasBranch('payments') ? $branchId : null,
                    'invoice_id' => $invId, 'patient_id' => $patient->id, 'payment_method_id' => $pmId,
                    'amount' => $paid, 'payment_date' => $date->toDateString(), 'received_by' => $adminId,
                    'created_at' => $date, 'updated_at' => $date,
                ], fn ($v) => $v !== null));
            }
        }

        // A couple of loyalty points so that screen is populated too.
        if (DB::getSchemaBuilder()->hasTable('loyalty_points') && DB::table('loyalty_points')->where('patient_id', $patient->id)->doesntExist()) {
            DB::table('loyalty_points')->insert([
                'patient_id' => $patient->id, 'points' => 150, 'type' => 'earn',
                'description' => 'نقاط مكتسبة من الزيارات', 'expires_at' => $now->copy()->addYear(),
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    /** All view/create/update permissions, minus delete and core-settings. */
    private function demoAdminPermissions(): array
    {
        $coreSettingsModules = ['settings', 'users', 'roles', 'permissions', 'modules_management', 'branches', 'ai'];
        $blockedActions = ['delete', 'destroy', 'force_delete', 'empty_trash', 'manage', 'prompts'];

        $perms = [];
        foreach ((array) config('permissions.modules', []) as $key => $module) {
            foreach ((array) ($module['actions'] ?? []) as $action) {
                if (in_array($action, $blockedActions, true)) {
                    continue;
                }
                if (in_array($key, $coreSettingsModules, true) && $action !== 'view') {
                    continue; // core settings: view only
                }
                $perms[] = "{$key}.{$action}";
            }
        }

        return array_values(array_unique($perms));
    }
}
