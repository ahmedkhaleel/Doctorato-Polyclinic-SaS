<?php

namespace App\Http\Requests\Dental;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDentalTreatmentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title_ar' => 'nullable|string|max:255',
            'title_en' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'estimated_cost' => 'nullable|numeric|min:0|max:999999.99',
            'estimated_sessions' => 'nullable|integer|min:1|max:200',
            'priority' => ['nullable', 'string', Rule::in(['urgent', 'high', 'normal', 'low'])],
            'status' => ['nullable', 'string', Rule::in(['draft', 'approved', 'in_progress', 'completed', 'cancelled'])],
            'start_date' => 'nullable|date',
            'expected_end_date' => 'nullable|date|after_or_equal:start_date',
            'notes' => 'nullable|string|max:5000',
        ];
    }

    public function attributes(): array
    {
        if (app()->getLocale() === 'ar') {
            return [
                'title_ar' => 'العنوان بالعربي',
                'title_en' => 'العنوان بالإنجليزي',
                'estimated_cost' => 'التكلفة التقديرية',
                'estimated_sessions' => 'عدد الجلسات',
                'priority' => 'الأولوية',
                'status' => 'الحالة',
                'start_date' => 'تاريخ البدء',
                'expected_end_date' => 'تاريخ الانتهاء المتوقع',
            ];
        }

        return [];
    }
}
