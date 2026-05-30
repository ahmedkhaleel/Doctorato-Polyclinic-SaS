<?php

namespace App\Http\Controllers\Patient;

use App\Models\Pregnancy;
use App\Services\ObstetricCalculatorService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PatientObgynController extends BasePatientController
{
    public function __construct(private ObstetricCalculatorService $calc) {}

    /**
     * The patient's own pregnancy follow-up: active pregnancy with the EDD
     * countdown + the antenatal timeline, ultrasounds and lab results, plus
     * a short history of past pregnancies.
     */
    public function overview(Request $request): Response
    {
        $patientId = $this->patientId($request);

        $active = Pregnancy::where('patient_id', $patientId)
            ->where('status', Pregnancy::STATUS_ACTIVE)
            ->with([
                'doctor:id,name_ar,name_en',
                'antenatalVisits',
                'ultrasounds',
                'labTests',
            ])
            ->latest('id')
            ->first();

        $activeData = null;
        if ($active) {
            $next = $active->lmp ? $this->calc->nextAncDate($active->lmp) : null;
            $activeData = [
                'id' => $active->id,
                'edd' => optional($active->edd)->toDateString(),
                'ga_label' => $active->lmp ? $this->calc->gestationalAgeLabel($active->lmp) : null,
                'ga_weeks' => $active->lmp ? $this->calc->gestationalAge($active->lmp)['decimal'] : null,
                'trimester' => $active->lmp ? $this->calc->trimester($this->calc->gestationalAge($active->lmp)['decimal']) : null,
                'days_until_edd' => $active->edd ? $this->calc->daysUntilEdd($active->edd) : null,
                'next_visit' => $next?->toDateString(),
                'is_high_risk' => (bool) $active->is_high_risk,
                'doctor' => $active->doctor,
                // Patient-safe fields only.
                'antenatal_visits' => $active->antenatalVisits->map(fn ($a) => [
                    'visit_date' => optional($a->visit_date)->toDateString(),
                    'gestational_age_weeks' => $a->gestational_age_weeks,
                    'weight_kg' => $a->weight_kg,
                    'blood_pressure' => $a->blood_pressure,
                    'next_visit_date' => optional($a->next_visit_date)->toDateString(),
                ]),
                'ultrasounds' => $active->ultrasounds->map(fn ($u) => [
                    'scan_date' => optional($u->scan_date)->toDateString(),
                    'scan_type' => $u->scan_type,
                    'gestational_age_weeks' => $u->gestational_age_weeks,
                    'efw_grams' => $u->efw_grams,
                    'findings' => $u->findings,
                ]),
                'lab_tests' => $active->labTests->map(fn ($l) => [
                    'test_type' => $l->test_type,
                    'value' => $l->value,
                    'unit' => $l->unit,
                    'reference_range' => $l->reference_range,
                    'result_date' => optional($l->result_date)->toDateString(),
                    'is_abnormal' => (bool) $l->is_abnormal,
                ]),
            ];
        }

        $history = Pregnancy::where('patient_id', $patientId)
            ->where('status', '!=', Pregnancy::STATUS_ACTIVE)
            ->with('delivery')
            ->latest('lmp')
            ->limit(10)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'status' => $p->status,
                'lmp' => optional($p->lmp)->toDateString(),
                'delivery' => $p->delivery ? [
                    'delivery_date' => optional($p->delivery->delivery_date)->toDateString(),
                    'delivery_mode' => $p->delivery->delivery_mode,
                    'outcome' => $p->delivery->outcome,
                ] : null,
            ]);

        return Inertia::render('Patient/Obgyn/Overview', [
            'pregnancy' => $activeData,
            'history' => $history,
            'ancSchedule' => ObstetricCalculatorService::WHO_ANC_WEEKS,
        ]);
    }
}
