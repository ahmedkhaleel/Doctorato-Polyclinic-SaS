<?php

namespace App\Http\Requests\Dental;

use App\Models\DentalChart;
use App\Models\DentalTreatment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDentalTreatmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validTeeth = array_merge(DentalChart::ALL_TEETH, DentalChart::ALL_DECIDUOUS_TEETH);

        return [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'template_id' => 'nullable|exists:dental_treatment_plan_templates,id',
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'estimated_cost' => 'nullable|numeric|min:0|max:999999.99',
            'estimated_sessions' => 'nullable|integer|min:1|max:200',
            'priority' => ['nullable', 'string', Rule::in(['urgent', 'high', 'normal', 'low'])],
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:5000',
            'treatments' => 'nullable|array|max:50',
            'treatments.*.tooth_number' => ['nullable', 'integer', Rule::in($validTeeth)],
            'treatments.*.treatment_type' => ['required', 'string', Rule::in(DentalTreatment::TYPES)],
            'treatments.*.surfaces' => 'nullable|array',
            'treatments.*.surfaces.*' => ['string', Rule::in(DentalChart::SURFACES)],
            'treatments.*.description' => 'nullable|string|max:1000',
            'treatments.*.cost' => 'nullable|numeric|min:0|max:999999.99',
            'treatments.*.lab_cost' => 'nullable|numeric|min:0|max:999999.99',
        ];
    }

    public function attributes(): array
    {
        if (app()->getLocale() === 'ar') {
            return [
                'patient_id' => 'المريض',
                'doctor_id' => 'الطبيب',
                'title_ar' => 'العنوان بالعربي',
                'title_en' => 'العنوان بالإنجليزي',
                'estimated_cost' => 'التكلفة التقديرية',
                'estimated_sessions' => 'عدد الجلسات',
                'priority' => 'الأولوية',
                'start_date' => 'تاريخ البدء',
                'expected_end_date' => 'تاريخ الانتهاء المتوقع',
            ];
        }

        return [];
    }
}
