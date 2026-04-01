<?php

namespace App\Http\Requests\Dental;

use App\Models\DentalTreatment;
use Illuminate\Foundation\Http\FormRequest;

class StoreDentalPrescriptionTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'treatment_type' => 'nullable|string|in:' . implode(',', DentalTreatment::TYPES),
            'diagnosis_ar' => 'nullable|string|max:2000',
            'diagnosis_en' => 'nullable|string|max:2000',
            'notes_ar' => 'nullable|string|max:2000',
            'notes_en' => 'nullable|string|max:2000',
            'auto_apply' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0|max:999',
            'items' => 'required|array|min:1|max:30',
            'items.*.medication_name' => 'required|string|max:255',
            'items.*.dosage' => 'nullable|string|max:255',
            'items.*.frequency' => 'nullable|string|max:255',
            'items.*.duration' => 'nullable|string|max:255',
            'items.*.instructions_ar' => 'nullable|string|max:500',
            'items.*.instructions_en' => 'nullable|string|max:500',
        ];
    }

    public function attributes(): array
    {
        if (app()->getLocale() === 'ar') {
            return [
                'name_ar' => 'الاسم بالعربي',
                'name_en' => 'الاسم بالإنجليزي',
                'treatment_type' => 'نوع العلاج',
                'items' => 'الأدوية',
                'items.*.medication_name' => 'اسم الدواء',
            ];
        }

        return [];
    }
}
