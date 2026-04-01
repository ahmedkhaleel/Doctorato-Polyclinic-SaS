<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DentalChart;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PeriodontalChart;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PeriodontalChartController extends Controller
{
    public function show(Patient $patient, Request $request)
    {
        $examDate = $request->get('exam_date', now()->toDateString());

        $records = PeriodontalChart::where('patient_id', $patient->id)
            ->where('exam_date', $examDate)
            ->orderBy('tooth_number')
            ->get()
            ->keyBy('tooth_number');

        $examDates = PeriodontalChart::where('patient_id', $patient->id)
            ->select('exam_date')
            ->distinct()
            ->orderByDesc('exam_date')
            ->pluck('exam_date');

        return Inertia::render('Admin/Dental/Periodontal/Show', [
            'patient' => $patient->only(['id', 'full_name', 'file_number']),
            'records' => $records,
            'examDate' => $examDate,
            'examDates' => $examDates,
            'allTeeth' => DentalChart::ALL_TEETH,
            'doctors' => Doctor::dental()->active()->select('id', 'name_ar', 'name_en')->get(),
        ]);
    }

    public function store(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'exam_date' => 'required|date',
            'measurements' => 'required|array',
            'measurements.*.tooth_number' => 'required|integer',
            'measurements.*.probing_depths' => 'nullable|array|max:6',
            'measurements.*.recession' => 'nullable|array|max:6',
            'measurements.*.bleeding_on_probing' => 'nullable|array|max:6',
            'measurements.*.plaque_index' => 'nullable|array|max:6',
            'measurements.*.mobility' => 'nullable|integer|min:0|max:3',
            'measurements.*.furcation' => 'nullable|integer|min:0|max:3',
            'measurements.*.notes' => 'nullable|string',
        ]);

        foreach ($data['measurements'] as $measurement) {
            PeriodontalChart::updateOrCreate(
                [
                    'patient_id' => $patient->id,
                    'exam_date' => $data['exam_date'],
                    'tooth_number' => $measurement['tooth_number'],
                ],
                [
                    'doctor_id' => $data['doctor_id'],
                    'probing_depths' => $measurement['probing_depths'] ?? null,
                    'recession' => $measurement['recession'] ?? null,
                    'bleeding_on_probing' => $measurement['bleeding_on_probing'] ?? null,
                    'plaque_index' => $measurement['plaque_index'] ?? null,
                    'mobility' => $measurement['mobility'] ?? null,
                    'furcation' => $measurement['furcation'] ?? null,
                    'notes' => $measurement['notes'] ?? null,
                ]
            );
        }

        AuditLogger::log('created', $patient, [
            'exam_date' => $data['exam_date'],
            'teeth_count' => count($data['measurements']),
            'doctor_id' => $data['doctor_id'],
        ], "Saved periodontal exam for patient {$patient->file_number}");

        return redirect()->back()->with('success', 'تم حفظ فحص اللثة بنجاح');
    }
}
