<?php

namespace App\Http\Controllers\Doctor;

use App\Models\AntenatalVisit;
use App\Models\ContraceptionRecord;
use App\Models\DeliveryRecord;
use App\Models\PapSmearScreening;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Models\Setting;
use App\Models\Visit;
use App\Services\AuditLogger;
use App\Services\ObgynBillingService;
use App\Services\ObstetricCalculatorService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class DoctorObgynController extends BaseDoctorController
{
    public function __construct(
        private ObstetricCalculatorService $calc,
        private ObgynBillingService $billing,
    ) {}

    public function dashboard(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $activePregnancies = Pregnancy::active()->where('doctor_id', $doctorId)->count();
        $highRisk = Pregnancy::active()->where('doctor_id', $doctorId)->where('is_high_risk', true)->count();

        $ancThisMonth = AntenatalVisit::where('doctor_id', $doctorId)
            ->whereMonth('visit_date', now()->month)
            ->whereYear('visit_date', now()->year)
            ->count();

        $deliveriesThisMonth = DeliveryRecord::where('doctor_id', $doctorId)
            ->whereMonth('delivery_date', now()->month)
            ->whereYear('delivery_date', now()->year)
            ->count();

        // Pregnancies whose EDD is within 30 days.
        $upcomingDue = Pregnancy::active()->where('doctor_id', $doctorId)
            ->whereNotNull('edd')
            ->whereBetween('edd', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->with('patient:id,full_name,phone,photo')
            ->orderBy('edd')
            ->limit(10)
            ->get()
            ->map(fn ($p) => $this->decoratePregnancy($p));

        $todayVisits = Visit::where('doctor_id', $doctorId)
            ->where('module', 'obgyn')
            ->whereDate('visit_date', today())
            ->with('patient:id,full_name,photo')
            ->orderBy('scheduled_time')
            ->get();

        return Inertia::render('Doctor/Obgyn/Dashboard', [
            'stats' => [
                'active_pregnancies' => $activePregnancies,
                'high_risk' => $highRisk,
                'anc_this_month' => $ancThisMonth,
                'deliveries_this_month' => $deliveriesThisMonth,
            ],
            'upcomingDue' => $upcomingDue,
            'todayVisits' => $todayVisits,
        ]);
    }

    public function pregnancies(Request $request): Response
    {
        $doctorId = $this->doctorId($request);
        $search = $request->input('search');
        $status = $request->input('status', 'active');

        $pregnancies = Pregnancy::query()
            ->where('doctor_id', $doctorId)
            ->when($status && $status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->whereHas('patient', fn ($pq) => $pq->where('full_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->with('patient:id,full_name,phone,photo')
            ->latest('lmp')
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($p) => $this->decoratePregnancy($p));

        // Female patients for the "open pregnancy" picker.
        $patients = Patient::where('gender', 'female')
            ->where('is_active', true)
            ->when($search, fn ($q) => $q->where('full_name', 'like', "%{$search}%"))
            ->orderBy('full_name')
            ->limit(50)
            ->get(['id', 'full_name', 'phone', 'file_number']);

        return Inertia::render('Doctor/Obgyn/Pregnancies/Index', [
            'pregnancies' => $pregnancies,
            'patients' => $patients,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function pregnancyShow(Request $request, Pregnancy $pregnancy): Response
    {
        $pregnancy->load([
            'patient',
            'doctor:id,name_ar,name_en',
            'antenatalVisits',
            'ultrasounds',
            'labTests',
            'delivery',
        ]);

        $supplies = \App\Services\ModuleManager::isEnabled('inventory')
            ? \App\Models\Supply::orderBy('name_ar')->get(['id', 'name_ar', 'name_en', 'unit', 'quantity'])
            : [];

        return Inertia::render('Doctor/Obgyn/Pregnancies/Show', [
            'pregnancy' => $this->decoratePregnancy($pregnancy),
            'antenatalVisits' => $pregnancy->antenatalVisits,
            'ultrasounds' => $pregnancy->ultrasounds,
            'labTests' => $pregnancy->labTests,
            'delivery' => $pregnancy->delivery,
            'supplies' => $supplies,
            'ancSchedule' => ObstetricCalculatorService::WHO_ANC_WEEKS,
        ]);
    }

    public function storePregnancy(Request $request): RedirectResponse
    {
        $doctorId = $this->doctorId($request);

        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'lmp' => 'nullable|date',
            'edd' => 'nullable|date',
            'gravida' => 'nullable|integer|min:0',
            'para' => 'nullable|integer|min:0',
            'conception_method' => 'nullable|string|max:30',
            'blood_group' => 'nullable|string|max:5',
            'rh_factor' => 'nullable|string|max:10',
            'is_high_risk' => 'boolean',
            'risk_factors' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        $patient = Patient::findOrFail($data['patient_id']);
        if ($patient->gender !== 'female') {
            throw ValidationException::withMessages(['patient_id' => 'Obstetric records are only for female patients.']);
        }
        if (Pregnancy::active()->where('patient_id', $patient->id)->exists()) {
            throw ValidationException::withMessages(['patient_id' => 'This patient already has an active pregnancy.']);
        }

        // Derive EDD/LMP if only one given.
        if (! empty($data['lmp']) && empty($data['edd'])) {
            $data['edd'] = $this->calc->eddFromLmp($data['lmp'])->toDateString();
            $data['edd_source'] = 'lmp';
        } elseif (! empty($data['edd']) && empty($data['lmp'])) {
            $data['lmp'] = $this->calc->lmpFromEdd($data['edd'])->toDateString();
            $data['edd_source'] = 'scan';
        }

        $data['doctor_id'] = $doctorId;
        $data['status'] = Pregnancy::STATUS_ACTIVE;

        $pregnancy = Pregnancy::create($data);
        AuditLogger::log('created', $pregnancy, ['patient_id' => $patient->id], 'Opened pregnancy file');

        return redirect()
            ->route('doctor.obgyn.pregnancies.show', $pregnancy->id)
            ->with('success', $this->msg('Pregnancy file opened.', 'تم فتح ملف الحمل.'));
    }

    public function storeAntenatalVisit(Request $request, Pregnancy $pregnancy): RedirectResponse
    {
        $data = $request->validate([
            'visit_date' => 'required|date',
            'phase' => 'nullable|in:antenatal,postnatal',
            'weight_kg' => 'nullable|numeric|min:0',
            'bp_systolic' => 'nullable|integer|min:0',
            'bp_diastolic' => 'nullable|integer|min:0',
            'fundal_height_cm' => 'nullable|numeric|min:0',
            'fetal_heart_rate' => 'nullable|integer|min:0',
            'presentation' => 'nullable|string|max:30',
            'edema' => 'boolean',
            'urine_protein' => 'nullable|string|max:30',
            'urine_glucose' => 'nullable|string|max:30',
            'complaints' => 'nullable|string',
            'plan' => 'nullable|string',
            'next_visit_date' => 'nullable|date',
            'bill' => 'boolean',
        ]);

        // Auto gestational age from LMP.
        if ($pregnancy->lmp) {
            $data['gestational_age_weeks'] = $this->calc->gestationalAge($pregnancy->lmp, $data['visit_date'])['decimal'];
        }
        $data['doctor_id'] = $this->doctorId($request);
        $data['phase'] ??= AntenatalVisit::PHASE_ANTENATAL;

        $anc = $pregnancy->antenatalVisits()->create($data);

        if ($request->boolean('bill', true)) {
            $this->billing->billAntenatalVisit($anc);
        }

        return back()->with('success', $this->msg('Antenatal visit recorded.', 'تم تسجيل زيارة المتابعة.'));
    }

    public function storeUltrasound(Request $request, Pregnancy $pregnancy): RedirectResponse
    {
        $data = $request->validate([
            'scan_date' => 'required|date',
            'scan_type' => 'required|in:dating,anomaly,growth,doppler',
            'gestational_age_weeks' => 'nullable|numeric|min:0',
            'bpd_mm' => 'nullable|numeric|min:0',
            'hc_mm' => 'nullable|numeric|min:0',
            'ac_mm' => 'nullable|numeric|min:0',
            'fl_mm' => 'nullable|numeric|min:0',
            'efw_grams' => 'nullable|integer|min:0',
            'placenta_position' => 'nullable|string|max:50',
            'afi' => 'nullable|numeric|min:0',
            'fetal_count' => 'nullable|integer|min:1',
            'fetal_heart' => 'boolean',
            'presentation' => 'nullable|string|max:30',
            'findings' => 'nullable|string',
            'supply_id' => 'nullable|exists:supplies,id',
            'consumption_qty' => 'nullable|numeric|min:0',
            'bill' => 'boolean',
        ]);
        $data['doctor_id'] = $this->doctorId($request);

        $scan = $pregnancy->ultrasounds()->create($data);

        if ($request->boolean('bill', true)) {
            $this->billing->billUltrasound($scan);
        }
        $this->billing->consumeInventory($scan->fresh());

        return back()->with('success', $this->msg('Ultrasound recorded.', 'تم تسجيل السونار.'));
    }

    public function storeLab(Request $request, Pregnancy $pregnancy): RedirectResponse
    {
        $data = $request->validate([
            'test_type' => 'required|string|max:100',
            'value' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:30',
            'reference_range' => 'nullable|string|max:50',
            'result_date' => 'nullable|date',
            'is_abnormal' => 'boolean',
            'notes' => 'nullable|string',
        ]);
        $data['doctor_id'] = $this->doctorId($request);
        $data['patient_id'] = $pregnancy->patient_id;

        $pregnancy->labTests()->create($data);

        return back()->with('success', $this->msg('Lab result added.', 'تمت إضافة نتيجة التحليل.'));
    }

    public function storeDelivery(Request $request, Pregnancy $pregnancy): RedirectResponse
    {
        $data = $request->validate([
            'delivery_date' => 'required|date',
            'delivery_mode' => 'required|in:nvd,cesarean,instrumental',
            'place' => 'nullable|string|max:100',
            'gestational_age_at_delivery' => 'nullable|numeric|min:0',
            'outcome' => 'required|in:live,stillbirth',
            'baby_weight_grams' => 'nullable|integer|min:0',
            'baby_sex' => 'nullable|in:male,female',
            'apgar_1' => 'nullable|integer|min:0|max:10',
            'apgar_5' => 'nullable|integer|min:0|max:10',
            'complications' => 'nullable|string',
            'notes' => 'nullable|string',
            'supply_id' => 'nullable|exists:supplies,id',
            'consumption_qty' => 'nullable|numeric|min:0',
            'create_newborn' => 'boolean',
            'bill' => 'boolean',
        ]);

        if ($pregnancy->delivery) {
            throw ValidationException::withMessages(['delivery_date' => 'A delivery is already recorded for this pregnancy.']);
        }
        $data['doctor_id'] = $this->doctorId($request);

        // Cross-module: register the live newborn as a pediatric patient.
        if ($request->boolean('create_newborn') && $data['outcome'] === 'live') {
            $mother = $pregnancy->patient;
            $newborn = Patient::create([
                'full_name' => $this->msg('Newborn of ', 'مولود ').($mother->full_name ?? ''),
                'phone' => $mother->phone,
                'gender' => $data['baby_sex'] ?? null,
                'date_of_birth' => $data['delivery_date'],
                'guardian_name' => $mother->full_name,
                'guardian_phone' => $mother->phone,
            ]);
            $data['newborn_patient_id'] = $newborn->id;
        }

        $delivery = $pregnancy->delivery()->create($data);
        $pregnancy->update(['status' => Pregnancy::STATUS_DELIVERED]);

        if ($request->boolean('bill', true)) {
            $this->billing->billDelivery($delivery);
        }
        $this->billing->consumeInventory($delivery->fresh());

        AuditLogger::log('updated', $pregnancy, ['delivery' => $delivery->id], 'Recorded delivery');

        return back()->with('success', $this->msg('Delivery recorded.', 'تم تسجيل الولادة.'));
    }

    /**
     * Gynecology workspace — pap-smear screenings + contraception records.
     * The obstetrics side lives under pregnancies; this covers the gyn side.
     */
    public function gynecology(Request $request): Response
    {
        $doctorId = $this->doctorId($request);

        $papSmears = PapSmearScreening::where('doctor_id', $doctorId)
            ->with('patient:id,full_name,phone')
            ->latest('test_date')->limit(40)->get();

        $contraception = ContraceptionRecord::where('doctor_id', $doctorId)
            ->with('patient:id,full_name,phone')
            ->latest('start_date')->limit(40)->get();

        $patients = Patient::where('gender', 'female')->where('is_active', true)
            ->orderBy('full_name')->limit(50)
            ->get(['id', 'full_name', 'phone', 'file_number']);

        return Inertia::render('Doctor/Obgyn/Gynecology', [
            'papSmears' => $papSmears,
            'contraception' => $contraception,
            'patients' => $patients,
        ]);
    }

    public function storePapSmear(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'test_date' => 'required|date',
            'result' => 'nullable|in:normal,ascus,lsil,hsil,cancer',
            'hpv_status' => 'nullable|in:positive,negative,unknown',
            'next_due_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'bill' => 'boolean',
        ]);
        $patient = Patient::findOrFail($data['patient_id']);
        if ($patient->gender !== 'female') {
            throw ValidationException::withMessages(['patient_id' => 'Gynecology records are only for female patients.']);
        }
        $data['doctor_id'] = $this->doctorId($request);

        $pap = PapSmearScreening::create($data);
        if ($request->boolean('bill', true)) {
            $this->billing->billPapSmear($pap);
        }

        return back()->with('success', $this->msg('Pap smear recorded.', 'تم تسجيل المسحة.'));
    }

    public function storeContraception(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'method' => 'required|string|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'follow_up_date' => 'nullable|date',
            'status' => 'nullable|in:active,stopped',
            'notes' => 'nullable|string',
        ]);
        $patient = Patient::findOrFail($data['patient_id']);
        if ($patient->gender !== 'female') {
            throw ValidationException::withMessages(['patient_id' => 'Gynecology records are only for female patients.']);
        }
        $data['doctor_id'] = $this->doctorId($request);
        $data['status'] ??= ContraceptionRecord::STATUS_ACTIVE;

        ContraceptionRecord::create($data);

        return back()->with('success', $this->msg('Contraception record saved.', 'تم حفظ سجل منع الحمل.'));
    }

    /**
     * Printable antenatal card — pregnancy summary + ANC visits + ultrasounds.
     */
    public function antenatalCard(Request $request, Pregnancy $pregnancy)
    {
        $pregnancy->load(['patient', 'doctor:id,name_ar,name_en', 'antenatalVisits', 'ultrasounds']);

        $pdf = Pdf::loadView('pdf.obgyn-antenatal-card', [
            'pregnancy' => $pregnancy,
            'gaLabel' => $pregnancy->lmp ? $this->calc->gestationalAgeLabel($pregnancy->lmp) : '—',
            'clinicName' => Setting::get('clinic_name', 'Doctorato Polyclinic'),
            'clinicPhone' => Setting::get('clinic_phone', ''),
        ]);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'AntenatalCard-'.($pregnancy->patient->file_number ?? $pregnancy->id).'.pdf';

        return $pdf->stream($filename);
    }

    /**
     * Attach calculator-derived gestational age / EDD / trimester / countdown.
     */
    private function decoratePregnancy(Pregnancy $p): Pregnancy
    {
        if ($p->lmp) {
            $ga = $this->calc->gestationalAge($p->lmp);
            $p->setAttribute('ga_label', $this->calc->gestationalAgeLabel($p->lmp));
            $p->setAttribute('ga_weeks', $ga['decimal']);
            $p->setAttribute('trimester', $this->calc->trimester($ga['decimal']));
        }
        if ($p->edd) {
            $p->setAttribute('days_until_edd', $this->calc->daysUntilEdd($p->edd));
        }

        return $p;
    }
}
