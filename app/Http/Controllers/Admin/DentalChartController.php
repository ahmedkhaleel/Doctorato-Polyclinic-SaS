<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DentalChart;
use App\Models\DentalChartEntry;
use App\Models\DentalTreatment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Http\Requests\Dental\UpdateDentalChartRequest;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DentalChartController extends Controller
{
    /**
     * Search patients to open their dental chart.
     */
    public function search(Request $request)
    {
        $search = $request->input('search', '');
        $patients = [];

        if (strlen($search) >= 2) {
            $patients = Patient::where(function ($q) use ($search) {
                    $q->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('file_number', 'like', "%{$search}%");
                })
                ->select('id', 'full_name', 'phone', 'file_number', 'photo')
                ->take(10)
                ->get();
        }

        // Recent patients who have dental charts
        $recentCharts = Patient::whereHas('dentalCharts')
            ->select('id', 'full_name', 'phone', 'file_number')
            ->withCount('dentalCharts as teeth_count')
            ->latest('updated_at')
            ->take(6)
            ->get();

        return Inertia::render('Admin/Dental/DentalChart/Search', [
            'patients' => $patients,
            'recentCharts' => $recentCharts,
            'search' => $search,
        ]);
    }

    public function show(Patient $patient)
    {
        $chart = DentalChart::where('patient_id', $patient->id)
            ->orderBy('tooth_number')
            ->get()
            ->keyBy('tooth_number');

        $treatments = DentalTreatment::where('patient_id', $patient->id)
            ->with('doctor:id,name_ar,name_en')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('tooth_number');

        // Load history entries grouped by tooth_number
        $entries = DentalChartEntry::where('patient_id', $patient->id)
            ->with('doctor:id,name_ar,name_en')
            ->orderByDesc('entry_date')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('tooth_number');

        $doctors = Doctor::select('id', 'name_en', 'name_ar')
            ->where('status', 'active')
            ->orderBy('name_en')
            ->get();

        // Determine if patient is a child (under 13) for deciduous chart
        $isChild = false;
        if ($patient->date_of_birth) {
            $age = \Carbon\Carbon::parse($patient->date_of_birth)->age;
            $isChild = $age < 13;
        }

        return Inertia::render('Admin/Dental/DentalChart/Show', [
            'patient' => $patient->only(['id', 'full_name', 'file_number', 'phone', 'date_of_birth', 'gender']),
            'chart' => $chart,
            'treatments' => $treatments,
            'entries' => $entries,
            'doctors' => $doctors,
            'conditions' => DentalChart::CONDITIONS,
            'surfaces' => DentalChart::SURFACES,
            'allTeeth' => DentalChart::ALL_TEETH,
            'deciduousTeeth' => DentalChart::ALL_DECIDUOUS_TEETH,
            'isChild' => $isChild,
        ]);
    }

    public function updateTooth(UpdateDentalChartRequest $request, Patient $patient, int $toothNumber)
    {
        $data = $request->validated();

        $chart = DentalChart::updateOrCreate(
            ['patient_id' => $patient->id, 'tooth_number' => $toothNumber],
            $data
        );

        AuditLogger::log($chart->wasRecentlyCreated ? 'created' : 'updated', $chart);

        return redirect()->back()->with('success', 'تم تحديث حالة السن بنجاح');
    }

    /**
     * Add a history entry for a specific tooth with optional media uploads.
     */
    public function addEntry(Request $request, Patient $patient, int $toothNumber)
    {
        $data = $request->validate([
            'entry_type' => 'required|string|in:examination,treatment,note,follow_up,complication,media_only',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'condition_before' => 'nullable|string|max:50',
            'condition_after' => 'nullable|string|max:50',
            'surfaces' => 'nullable|array',
            'surfaces.*' => 'string|in:mesial,distal,occlusal,buccal,lingual',
            'cost' => 'nullable|numeric|min:0|max:999999',
            'status' => 'nullable|string|in:recorded,in_progress,completed',
            'doctor_id' => 'nullable|exists:doctors,id',
            'entry_date' => 'nullable|date',
            'files.*' => 'nullable|file|max:20480|mimes:jpg,jpeg,png,webp,gif,mp4,mov,pdf,doc,docx',
        ]);

        // Handle file uploads
        $media = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $ext = strtolower($file->getClientOriginalExtension());
                $type = in_array($ext, ['mp4', 'mov']) ? 'video'
                    : (in_array($ext, ['pdf', 'doc', 'docx']) ? 'document' : 'image');

                $path = $file->store('uploads/dental/entries/' . $patient->id, 'local');
                $media[] = [
                    'type' => $type,
                    'path' => $path,
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'caption' => null,
                    'uploaded_at' => now()->toISOString(),
                ];
            }
        }

        $entry = DentalChartEntry::create([
            'patient_id' => $patient->id,
            'doctor_id' => $data['doctor_id'] ?? null,
            'tooth_number' => $toothNumber,
            'entry_type' => $data['entry_type'],
            'title' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'condition_before' => $data['condition_before'] ?? null,
            'condition_after' => $data['condition_after'] ?? null,
            'surfaces' => $data['surfaces'] ?? null,
            'cost' => $data['cost'] ?? 0,
            'status' => $data['status'] ?? 'recorded',
            'media' => !empty($media) ? $media : null,
            'entry_date' => $data['entry_date'] ?? now()->toDateString(),
        ]);

        AuditLogger::log('created', $entry);

        return redirect()->back()->with('success', 'تمت إضافة السجل بنجاح');
    }

    /**
     * Delete a history entry.
     */
    public function deleteEntry(Patient $patient, DentalChartEntry $entry)
    {
        if ($entry->patient_id !== $patient->id) {
            abort(403);
        }

        // Delete media files
        if ($entry->media) {
            foreach ($entry->media as $m) {
                \App\Support\SecureMedia::delete($m['path'] ?? '');
            }
        }

        AuditLogger::log('deleted', $entry);
        $entry->delete();

        return redirect()->back()->with('success', 'تم حذف السجل');
    }

    public function initializeChart(Request $request, Patient $patient)
    {
        $mode = $request->input('mode', 'adult'); // adult or deciduous
        $teethList = $mode === 'deciduous' ? DentalChart::ALL_DECIDUOUS_TEETH : DentalChart::ALL_TEETH;

        $existing = DentalChart::where('patient_id', $patient->id)->pluck('tooth_number')->toArray();
        $toCreate = array_diff($teethList, $existing);

        $records = [];
        $now = now();
        foreach ($toCreate as $tooth) {
            $records[] = [
                'patient_id' => $patient->id,
                'tooth_number' => $tooth,
                'condition' => 'healthy',
                'status' => 'present',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($records)) {
            DentalChart::insert($records);

            $label = $mode === 'deciduous' ? 'deciduous' : 'adult';
            AuditLogger::log('created', null, [
                'new' => ['patient_id' => $patient->id, 'teeth_count' => count($records), 'mode' => $label],
            ], "Initialized {$label} dental chart for patient \"{$patient->full_name}\" (" . count($records) . ' teeth)');
        }

        return redirect()->back()->with('success', 'تم إنشاء رسم الأسنان بنجاح');
    }
}
