<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AntenatalVisit;
use App\Models\DeliveryRecord;
use App\Models\Invoice;
use App\Models\ObgynLabTest;
use App\Models\ObgynProfile;
use App\Models\PapSmearScreening;
use App\Models\Pregnancy;
use App\Services\ModuleManager;
use App\Services\ObstetricCalculatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminObgynController extends Controller
{
    public function __construct(private ObstetricCalculatorService $calc) {}

    public function dashboard(): Response
    {
        $active = Pregnancy::active()->get(['id', 'lmp', 'edd', 'is_high_risk']);

        $trimesters = [1 => 0, 2 => 0, 3 => 0];
        foreach ($active as $p) {
            if ($p->lmp) {
                $t = $this->calc->trimester($this->calc->gestationalAge($p->lmp)['decimal']);
                $trimesters[$t]++;
            }
        }

        $revenueMonth = Invoice::where('module', 'obgyn')
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->sum('total');

        $upcomingDue = Pregnancy::active()
            ->whereNotNull('edd')
            ->whereBetween('edd', [now()->toDateString(), now()->addDays(30)->toDateString()])
            ->with('patient:id,full_name,phone', 'doctor:id,name_ar,name_en')
            ->orderBy('edd')->limit(8)->get()
            ->map(fn ($p) => $this->decorate($p));

        return Inertia::render('Admin/Obgyn/Dashboard', [
            'stats' => [
                'active_pregnancies' => $active->count(),
                'high_risk' => $active->where('is_high_risk', true)->count(),
                'anc_this_month' => DB::table('antenatal_visits')->whereMonth('visit_date', now()->month)->whereYear('visit_date', now()->year)->whereNull('deleted_at')->count(),
                'deliveries_this_month' => DeliveryRecord::whereMonth('delivery_date', now()->month)->whereYear('delivery_date', now()->year)->count(),
                'revenue_this_month' => (float) $revenueMonth,
            ],
            'trimesters' => $trimesters,
            'upcomingDue' => $upcomingDue,
        ]);
    }

    public function pregnancies(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all');

        $pregnancies = Pregnancy::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->whereHas('patient', fn ($pq) => $pq->where('full_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->with('patient:id,full_name,phone', 'doctor:id,name_ar,name_en')
            ->latest('lmp')
            ->paginate(20)->withQueryString()
            ->through(fn ($p) => $this->decorate($p));

        return Inertia::render('Admin/Obgyn/Pregnancies', [
            'pregnancies' => $pregnancies,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    /**
     * O1 — Cases: every patient with an OB/GYN profile, with gravida/para and
     * whether they have an active pregnancy. Read-only oversight; drills into
     * the patient. (Encounters per se are covered by Pregnancies + ANC.)
     */
    public function cases(Request $request): Response
    {
        $search = trim((string) $request->input('search'));

        $cases = ObgynProfile::query()
            ->when($search !== '', fn ($q) => $q->whereHas('patient', fn ($pq) => $pq->where('full_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")->orWhere('file_number', 'like', "%{$search}%")))
            ->with('patient:id,full_name,phone,file_number', 'doctor:id,name_ar,name_en')
            ->latest('updated_at')
            ->paginate(20)->withQueryString();

        $pageIds = collect($cases->items())->pluck('patient_id')->all();
        $activePreg = Pregnancy::active()->whereIn('patient_id', $pageIds)->pluck('patient_id')->flip();

        $cases->getCollection()->transform(fn (ObgynProfile $p) => [
            'patient' => ['id' => $p->patient?->id, 'full_name' => $p->patient?->full_name, 'phone' => $p->patient?->phone, 'file_number' => $p->patient?->file_number],
            'doctor' => ['name_ar' => $p->doctor?->name_ar, 'name_en' => $p->doctor?->name_en],
            'gravida' => $p->gravida,
            'para' => $p->para,
            'blood_group' => $p->blood_group ? trim(($p->blood_group).($p->rh_factor ?? '')) : null,
            'has_active_pregnancy' => $activePreg->has($p->patient_id),
        ]);

        return Inertia::render('Admin/Obgyn/Cases', [
            'cases' => $cases,
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * O2 — ANC queue: active pregnancies whose next scheduled antenatal visit
     * is due soon or overdue, derived from each pregnancy's latest visit's
     * next_visit_date. A short operational work-queue (not paginated).
     */
    public function anc(Request $request): Response
    {
        $reminderDays = (int) (DB::table('module_settings')->where('module', 'obgyn')->where('key', 'anc_reminder_days')->value('value') ?? 7);
        $today = now()->startOfDay();
        $horizon = $today->copy()->addDays($reminderDays);

        // Latest antenatal visit per active pregnancy that has a next visit date.
        $latest = AntenatalVisit::whereNotNull('next_visit_date')
            ->whereHas('pregnancy', fn ($q) => $q->active())
            ->with(['pregnancy.patient:id,full_name,phone,file_number', 'pregnancy.doctor:id,name_ar,name_en'])
            ->get()
            ->groupBy('pregnancy_id')
            ->map(fn ($g) => $g->sortByDesc('visit_date')->first());

        $queue = $latest
            ->filter(fn ($v) => $v->next_visit_date && $v->next_visit_date <= $horizon)
            ->sortBy('next_visit_date')
            ->values()
            ->map(function ($v) use ($today) {
                $due = \Illuminate\Support\Carbon::parse($v->next_visit_date)->startOfDay();
                $days = $today->diffInDays($due, false);   // negative = overdue

                return [
                    'pregnancy_id' => $v->pregnancy_id,
                    'patient' => ['id' => $v->pregnancy?->patient?->id, 'full_name' => $v->pregnancy?->patient?->full_name, 'phone' => $v->pregnancy?->patient?->phone, 'file_number' => $v->pregnancy?->patient?->file_number],
                    'doctor' => ['name_ar' => $v->pregnancy?->doctor?->name_ar, 'name_en' => $v->pregnancy?->doctor?->name_en],
                    'last_visit' => optional($v->visit_date)->toDateString(),
                    'next_visit_date' => $due->toDateString(),
                    'days_until' => (int) $days,
                    'overdue' => $days < 0,
                ];
            });

        return Inertia::render('Admin/Obgyn/Anc', [
            'queue' => $queue,
            'reminderDays' => $reminderDays,
        ]);
    }

    /**
     * O3 — Lab tests oversight: OB/GYN lab results, defaulting to abnormal-first
     * for quick triage; filterable to all.
     */
    public function labs(Request $request): Response
    {
        $only = $request->input('filter', 'abnormal');    // abnormal | all

        $labs = ObgynLabTest::query()
            ->when($only === 'abnormal', fn ($q) => $q->where('is_abnormal', true))
            ->with('patient:id,full_name,phone,file_number')
            ->latest('result_date')
            ->paginate(25)->withQueryString()
            ->through(fn (ObgynLabTest $t) => [
                'id' => $t->id,
                'patient' => ['id' => $t->patient?->id, 'full_name' => $t->patient?->full_name, 'phone' => $t->patient?->phone],
                'test_type' => $t->test_type,
                'value' => $t->value,
                'unit' => $t->unit,
                'reference_range' => $t->reference_range,
                'is_abnormal' => (bool) $t->is_abnormal,
                'result_date' => optional($t->result_date)->toDateString(),
            ]);

        return Inertia::render('Admin/Obgyn/Labs', [
            'labs' => $labs,
            'filters' => ['filter' => $only],
        ]);
    }

    public function reports(): Response
    {
        $byStatus = Pregnancy::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');
        $byMode = DeliveryRecord::select('delivery_mode', DB::raw('count(*) as c'))->groupBy('delivery_mode')->pluck('c', 'delivery_mode');
        $byPap = PapSmearScreening::select('result', DB::raw('count(*) as c'))->whereNotNull('result')->groupBy('result')->pluck('c', 'result');

        // Revenue trend — last 6 months of obgyn invoices.
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $total = Invoice::where('module', 'obgyn')
                ->whereMonth('invoice_date', $m->month)->whereYear('invoice_date', $m->year)
                ->sum('total');
            $trend[] = ['label' => $m->format('M'), 'value' => (float) $total];
        }

        $topDoctors = Pregnancy::select('doctor_id', DB::raw('count(*) as c'))
            ->whereNotNull('doctor_id')->groupBy('doctor_id')
            ->with('doctor:id,name_ar,name_en')->orderByDesc('c')->limit(5)->get()
            ->map(fn ($r) => ['doctor' => $r->doctor, 'count' => $r->c]);

        return Inertia::render('Admin/Obgyn/Reports', [
            'byStatus' => $byStatus,
            'byMode' => $byMode,
            'byPap' => $byPap,
            'revenueTrend' => $trend,
            'topDoctors' => $topDoctors,
            'totals' => [
                'total_pregnancies' => Pregnancy::count(),
                'total_deliveries' => DeliveryRecord::count(),
                'revenue_year' => (float) Invoice::where('module', 'obgyn')->whereYear('invoice_date', now()->year)->sum('total'),
            ],
        ]);
    }

    public function settings(): Response
    {
        return Inertia::render('Admin/Obgyn/Settings', [
            'settings' => $this->moduleSettings(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'consultation_fee' => 'nullable|numeric|min:0',
            'anc_fee' => 'nullable|numeric|min:0',
            'ultrasound_fee' => 'nullable|numeric|min:0',
            'delivery_fee' => 'nullable|numeric|min:0',
            'lab_fee' => 'nullable|numeric|min:0',
            'pap_smear_fee' => 'nullable|numeric|min:0',
            'anc_reminder_days' => 'nullable|integer|min:0',
            'edd_alert_days' => 'nullable|integer|min:0',
            'pap_recall_months' => 'nullable|integer|min:0',
        ]);

        $now = now();
        foreach ($data as $key => $value) {
            if ($value === null) {
                continue;
            }
            DB::table('module_settings')->updateOrInsert(
                ['module' => 'obgyn', 'key' => $key],
                ['value' => (string) $value, 'group' => 'pricing', 'updated_at' => $now, 'created_at' => $now]
            );
        }

        ModuleManager::clearCache();

        return back()->with('success', 'Settings updated.');
    }

    private function moduleSettings(): array
    {
        return DB::table('module_settings')
            ->where('module', 'obgyn')
            ->whereIn('group', ['pricing', 'clinical'])
            ->pluck('value', 'key')
            ->toArray();
    }

    private function decorate(Pregnancy $p): Pregnancy
    {
        if ($p->lmp) {
            $ga = $this->calc->gestationalAge($p->lmp);
            $p->setAttribute('ga_label', $this->calc->gestationalAgeLabel($p->lmp));
            $p->setAttribute('trimester', $this->calc->trimester($ga['decimal']));
        }
        if ($p->edd) {
            $p->setAttribute('days_until_edd', $this->calc->daysUntilEdd($p->edd));
        }

        return $p;
    }
}
