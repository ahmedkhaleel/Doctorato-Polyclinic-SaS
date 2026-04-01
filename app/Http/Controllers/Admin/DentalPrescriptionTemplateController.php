<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dental\StoreDentalPrescriptionTemplateRequest;
use App\Models\DentalPrescriptionTemplate;
use App\Models\DentalPrescriptionTemplateItem;
use App\Models\DentalTreatment;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DentalPrescriptionTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = DentalPrescriptionTemplate::with('items')->withCount('items');

        if ($request->filled('treatment_type')) {
            $query->where('treatment_type', $request->treatment_type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                  ->orWhere('name_en', 'like', "%{$search}%");
            });
        }

        $templates = $query->orderBy('sort_order')->orderBy('name_ar')->paginate(20)->withQueryString();

        return Inertia::render('Admin/Dental/PrescriptionTemplates/Index', [
            'templates' => $templates,
            'filters' => $request->only(['treatment_type', 'search']),
            'treatmentTypes' => DentalTreatment::TYPES,
        ]);
    }

    public function store(StoreDentalPrescriptionTemplateRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $template = DentalPrescriptionTemplate::create([
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'],
                'treatment_type' => $data['treatment_type'] ?? null,
                'diagnosis_ar' => $data['diagnosis_ar'] ?? null,
                'diagnosis_en' => $data['diagnosis_en'] ?? null,
                'notes_ar' => $data['notes_ar'] ?? null,
                'notes_en' => $data['notes_en'] ?? null,
                'auto_apply' => $data['auto_apply'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            foreach ($data['items'] as $index => $item) {
                DentalPrescriptionTemplateItem::create([
                    'template_id' => $template->id,
                    'medication_name' => $item['medication_name'],
                    'dosage' => $item['dosage'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'instructions_ar' => $item['instructions_ar'] ?? null,
                    'instructions_en' => $item['instructions_en'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            AuditLogger::log('created', $template);
        });

        return redirect()->back()->with('success', 'تم إنشاء قالب الوصفة بنجاح');
    }

    public function update(StoreDentalPrescriptionTemplateRequest $request, DentalPrescriptionTemplate $template)
    {
        $data = $request->validated();

        DB::transaction(function () use ($template, $data) {
            $template->update([
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'],
                'treatment_type' => $data['treatment_type'] ?? null,
                'diagnosis_ar' => $data['diagnosis_ar'] ?? null,
                'diagnosis_en' => $data['diagnosis_en'] ?? null,
                'notes_ar' => $data['notes_ar'] ?? null,
                'notes_en' => $data['notes_en'] ?? null,
                'auto_apply' => $data['auto_apply'] ?? false,
                'is_active' => $data['is_active'] ?? true,
                'sort_order' => $data['sort_order'] ?? 0,
            ]);

            $template->items()->delete();

            foreach ($data['items'] as $index => $item) {
                DentalPrescriptionTemplateItem::create([
                    'template_id' => $template->id,
                    'medication_name' => $item['medication_name'],
                    'dosage' => $item['dosage'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'instructions_ar' => $item['instructions_ar'] ?? null,
                    'instructions_en' => $item['instructions_en'] ?? null,
                    'sort_order' => $index,
                ]);
            }

            AuditLogger::log('updated', $template);
        });

        return redirect()->back()->with('success', 'تم تحديث قالب الوصفة بنجاح');
    }

    public function destroy(DentalPrescriptionTemplate $template)
    {
        AuditLogger::log('deleted', $template);

        $template->items()->delete();
        $template->delete();

        return redirect()->back()->with('success', 'تم حذف قالب الوصفة بنجاح');
    }
}
