<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\ModuleManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Patient recall view — surfaces "lapsed" patients who haven't visited
 * in N days, so the front desk can reach out (WhatsApp / SMS / email)
 * and bring them back. This is one of the highest-leverage operational
 * tools a clinic has — recall campaigns are pure margin.
 */
class RecallController extends Controller
{
    public function index(Request $request): Response
    {
        $days   = max(30, min(720, (int) $request->input('days', 180)));
        $module = $request->input('module');
        $doctor = $request->input('doctor_id');
        $search = trim((string) $request->input('search', ''));

        $cutoff = now()->subDays($days);

        // Per-patient last visit aggregate. We compute it inline so the
        // page stays accurate without a maintained denormalised column.
        $lastVisitSubquery = DB::table('visits')
            ->select('patient_id',
                     DB::raw('MAX(visit_date) as last_visit_date'),
                     DB::raw('COUNT(*) as total_visits'),
                     DB::raw('MAX(doctor_id) as last_doctor_id'))
            ->whereIn('status', ['completed', 'in_progress'])
            ->whereNull('deleted_at');

        if ($module && in_array($module, ModuleManager::MEDICAL_MODULES, true)) {
            $lastVisitSubquery->where('module', $module);
        }

        $lastVisitSubquery->groupBy('patient_id');

        $patientsQ = Patient::query()
            ->joinSub($lastVisitSubquery, 'lv', 'lv.patient_id', '=', 'patients.id')
            ->where('lv.last_visit_date', '<', $cutoff->toDateString())
            ->where('patients.is_active', true)
            ->select(
                'patients.id', 'patients.full_name', 'patients.file_number',
                'patients.phone', 'patients.email',
                'lv.last_visit_date', 'lv.total_visits', 'lv.last_doctor_id'
            );

        if ($search !== '') {
            $patientsQ->where(function ($q) use ($search) {
                $q->where('patients.full_name', 'like', "%$search%")
                  ->orWhere('patients.file_number', 'like', "%$search%")
                  ->orWhere('patients.phone', 'like', "%$search%");
            });
        }

        if ($doctor) {
            $patientsQ->where('lv.last_doctor_id', (int) $doctor);
        }

        $patients = $patientsQ
            ->orderBy('lv.last_visit_date', 'asc') // longest-lapsed first
            ->paginate(25)
            ->withQueryString();

        // Hydrate last doctor names in a single follow-up query.
        $doctorIds = collect($patients->items())->pluck('last_doctor_id')->filter()->unique();
        $doctorNames = Doctor::whereIn('id', $doctorIds)->pluck('name_ar', 'id');
        $doctorNamesEn = Doctor::whereIn('id', $doctorIds)->pluck('name_en', 'id');

        $patients->through(function ($p) use ($doctorNames, $doctorNamesEn) {
            return [
                'id'                  => $p->id,
                'full_name'           => $p->full_name,
                'file_number'         => $p->file_number,
                'phone'               => $p->phone,
                'email'               => $p->email,
                'last_visit_date'     => $p->last_visit_date,
                'days_since'          => $p->last_visit_date
                                            ? \Carbon\Carbon::parse($p->last_visit_date)->diffInDays(now())
                                            : null,
                'total_visits'        => (int) $p->total_visits,
                'last_doctor_name_ar' => $doctorNames->get($p->last_doctor_id),
                'last_doctor_name_en' => $doctorNamesEn->get($p->last_doctor_id),
            ];
        });

        // For the doctor filter dropdown
        $doctorList = Doctor::active()
            ->select('id', 'name_ar', 'name_en')
            ->orderBy('name_en')
            ->get();

        $availableModules = collect(ModuleManager::MEDICAL_MODULES)
            ->filter(fn ($m) => ModuleManager::isEnabled($m))
            ->values()
            ->all();

        return Inertia::render('Admin/Recall/Index', [
            'patients'         => $patients,
            'doctors'          => $doctorList,
            'availableModules' => $availableModules,
            'filters'          => [
                'days'      => $days,
                'module'    => $module,
                'doctor_id' => $doctor,
                'search'    => $search,
            ],
        ]);
    }
}
