<?php

namespace App\Http\Requests\Dental;

use App\Models\DentalChart;
use App\Models\DentalComparison;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDentalComparisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validTeeth = array_merge(DentalChart::ALL_TEETH, DentalChart::ALL_DECIDUOUS_TEETH);

        return [
            'patient_id' => ['required', 'exists:patients,id'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'treatment_plan_id' => ['nullable', 'exists:dental_treatment_plans,id'],
            'title_ar' => ['nullable', 'string', 'max:255'],
            'title_en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', Rule::in(DentalComparison::CATEGORIES)],
            'before_image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'before_date' => ['nullable', 'date', 'before_or_equal:today'],
            'before_notes' => ['nullable', 'string', 'max:1000'],
            'after_image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'after_date' => ['nullable', 'date', 'before_or_equal:today'],
            'after_notes' => ['nullable', 'string', 'max:1000'],
            'tooth_numbers' => ['nullable', 'array', 'max:32'],
            'tooth_numbers.*' => ['integer', Rule::in($validTeeth)],
            'is_visible_to_patient' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        if (app()->getLocale() === 'ar') {
            return [
                'patient_id' => 'المريض',
                'category' => 'التصنيف',
                'before_image' => 'صورة قبل',
                'after_image' => 'صورة بعد',
                'before_date' => 'تاريخ صورة قبل',
                'after_date' => 'تاريخ صورة بعد',
                'tooth_numbers' => 'أرقام الأسنان',
            ];
        }

        return [];
    }

    public function messages(): array
    {
        $isAr = app()->getLocale() === 'ar';

        return [
            'before_image.mimes' => $isAr
                ? 'يجب أن تكون صورة "قبل" بصيغة: jpg, jpeg, png, webp'
                : 'The before image must be a file of type: jpg, jpeg, png, webp',
            'after_image.mimes' => $isAr
                ? 'يجب أن تكون صورة "بعد" بصيغة: jpg, jpeg, png, webp'
                : 'The after image must be a file of type: jpg, jpeg, png, webp',
            'before_image.max' => $isAr
                ? 'حجم صورة "قبل" يجب ألا يتجاوز 10 ميغابايت'
                : 'The before image must not be greater than 10MB',
            'after_image.max' => $isAr
                ? 'حجم صورة "بعد" يجب ألا يتجاوز 10 ميغابايت'
                : 'The after image must not be greater than 10MB',
        ];
    }
}
