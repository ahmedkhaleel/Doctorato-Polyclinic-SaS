<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dental\StoreDentalXrayRequest;
use App\Http\Requests\Dental\UpdateDentalXrayRequest;
use App\Models\DentalXray;
use App\Models\Doctor;
use App\Models\Patient;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DentalXrayController extends Controller
{
    public function index(Request $request)
    {
        $query = DentalXray::with([
            'patient:id,full_name,file_number',
            'doctor:id,name_ar,name_en',
        ]);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('patient_id')) {
            $query->where('patient_id', $request->patient_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('file_number', 'like', "%{$search}%");
                })
                ->orWhere('tooth_number', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('taken_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('taken_date', '<=', $request->date_to);
        }

        $xrays = $query->latest('taken_date')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Admin/Dental/Xrays/Index', [
            'xrays' => $xrays,
            'filters' => $request->only(['type', 'patient_id', 'search', 'date_from', 'date_to']),
            'xrayTypes' => DentalXray::TYPES,
        ]);
    }

    public function store(StoreDentalXrayRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('dental-xrays', 'local');
        }
        unset($data['image']);

        $xray = DentalXray::create($data);

        AuditLogger::log('created', $xray);

        return redirect()->back()->with('success', 'تم رفع الأشعة بنجاح');
    }

    public function update(UpdateDentalXrayRequest $request, DentalXray $xray)
    {
        $data = $request->validated();

        $xray->update($data);

        AuditLogger::log('updated', $xray);

        return redirect()->back()->with('success', 'تم تحديث الأشعة بنجاح');
    }

    public function destroy(DentalXray $xray)
    {
        AuditLogger::log('deleted', $xray);
        if ($xray->image_path) {
            \App\Support\SecureMedia::delete($xray->image_path);
        }
        $xray->delete();

        return redirect()->back()->with('success', 'تم حذف الأشعة بنجاح');
    }

    public function patientXrays(Patient $patient)
    {
        $xrays = DentalXray::where('patient_id', $patient->id)
            ->with('doctor:id,name_ar,name_en')
            ->latest('taken_date')
            ->get();

        return Inertia::render('Admin/Dental/Xrays/PatientXrays', [
            'patient' => $patient->only(['id', 'full_name', 'file_number']),
            'xrays' => $xrays,
            'xrayTypes' => DentalXray::TYPES,
            'doctors' => Doctor::dental()->active()->select('id', 'name_ar', 'name_en')->get(),
        ]);
    }
}
