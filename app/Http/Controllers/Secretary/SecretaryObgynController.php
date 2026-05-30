<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Pregnancy;
use App\Services\AuditLogger;
use App\Services\ObstetricCalculatorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Front-desk OB/GYN: reception looks up pregnancies, opens a pregnancy file
 * for a female patient, and assigns the attending doctor. Clinical data
 * (antenatal visits, ultrasounds, delivery) is entered by the doctor.
 */
class SecretaryObgynController extends BaseSecretaryController
{
    public function __construct(private ObstetricCalculatorService $calc) {}

    public function pregnancies(Request $request): Response
    {
        $search = $request->input('search');
        $status = $request->input('status', 'active');

        $pregnancies = Pregnancy::query()
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search, fn ($q) => $q->whereHas('patient', fn ($pq) => $pq->where('full_name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->with('patient:id,full_name,phone', 'doctor:id,name_ar,name_en')
            ->latest('lmp')
            ->paginate(20)->withQueryString()
            ->through(fn ($p) => $this->decorate($p));

        $patients = Patient::where('gender', 'female')->where('is_active', true)
            ->when($search, fn ($q) => $q->where('full_name', 'like', "%{$search}%"))
            ->orderBy('full_name')->limit(50)
            ->get(['id', 'full_name', 'phone', 'file_number']);

        $doctors = Doctor::obgyn()->where('status', 'active')->orderBy('name_ar')->get(['id', 'name_ar', 'name_en']);

        return Inertia::render('Secretary/Obgyn/Pregnancies/Index', [
            'pregnancies' => $pregnancies,
            'patients' => $patients,
            'doctors' => $doctors,
            'filters' => ['search' => $search, 'status' => $status],
        ]);
    }

    public function storePregnancy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'lmp' => 'nullable|date',
            'edd' => 'nullable|date',
            'is_high_risk' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $patient = Patient::findOrFail($data['patient_id']);
        if ($patient->gender !== 'female') {
            throw ValidationException::withMessages(['patient_id' => 'Obstetric records are only for female patients.']);
        }
        if (Pregnancy::active()->where('patient_id', $patient->id)->exists()) {
            throw ValidationException::withMessages(['patient_id' => 'This patient already has an active pregnancy.']);
        }

        if (! empty($data['lmp']) && empty($data['edd'])) {
            $data['edd'] = $this->calc->eddFromLmp($data['lmp'])->toDateString();
            $data['edd_source'] = 'lmp';
        } elseif (! empty($data['edd']) && empty($data['lmp'])) {
            $data['lmp'] = $this->calc->lmpFromEdd($data['edd'])->toDateString();
            $data['edd_source'] = 'scan';
        }
        $data['status'] = Pregnancy::STATUS_ACTIVE;

        $pregnancy = Pregnancy::create($data);
        AuditLogger::log('created', $pregnancy, ['patient_id' => $patient->id], 'Reception opened pregnancy file');

        return back()->with('success', $this->msg('Pregnancy file opened.', 'تم فتح ملف الحمل.'));
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
