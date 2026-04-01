<?php

namespace App\Http\Requests\Dental;

use App\Models\DentalChart;
use App\Models\DentalXray;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDentalXrayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validTeeth = array_merge(DentalChart::ALL_TEETH, DentalChart::ALL_DECIDUOUS_TEETH);

        return [
            'type' => ['nullable', 'string', Rule::in(DentalXray::TYPES)],
            'image' => 'nullable|file|mimes:jpg,jpeg,png,webp,dicom,dcm|max:20480',
            'tooth_number' => ['nullable', 'integer', Rule::in($validTeeth)],
            'findings' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:5000',
            'taken_date' => 'nullable|date|before_or_equal:today',
        ];
    }

    public function attributes(): array
    {
        if (app()->getLocale() === 'ar') {
            return [
                'type' => 'نوع الأشعة',
                'image' => 'صورة الأشعة',
                'tooth_number' => 'رقم السن',
                'findings' => 'النتائج',
                'notes' => 'ملاحظات',
                'taken_date' => 'تاريخ الالتقاط',
            ];
        }

        return [];
    }

    public function messages(): array
    {
        return [
            'image.mimes' => app()->getLocale() === 'ar'
                ? 'يجب أن تكون صورة الأشعة بصيغة: jpg, jpeg, png, webp, dicom, dcm'
                : 'The x-ray image must be a file of type: jpg, jpeg, png, webp, dicom, dcm',
            'image.max' => app()->getLocale() === 'ar'
                ? 'حجم صورة الأشعة يجب ألا يتجاوز 20 ميغابايت'
                : 'The x-ray image must not be greater than 20MB',
        ];
    }
}
