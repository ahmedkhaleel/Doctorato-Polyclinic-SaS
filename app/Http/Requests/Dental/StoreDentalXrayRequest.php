<?php

namespace App\Http\Requests\Dental;

use App\Models\DentalChart;
use App\Models\DentalXray;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDentalXrayRequest extends FormRequest
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
            'type' => ['required', 'string', Rule::in(DentalXray::TYPES)],
            'image' => 'required|file|mimes:jpg,jpeg,png,webp,dicom,dcm|max:20480', // 20MB max for CBCT/DICOM
            'tooth_number' => ['nullable', 'integer', Rule::in($validTeeth)],
            'findings' => 'nullable|string|max:5000',
            'notes' => 'nullable|string|max:5000',
            'taken_date' => 'required|date|before_or_equal:today',
        ];
    }

    public function attributes(): array
    {
        if (app()->getLocale() === 'ar') {
            return [
                'patient_id' => 'المريض',
                'doctor_id' => 'الطبيب',
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
            'taken_date.before_or_equal' => app()->getLocale() === 'ar'
                ? 'تاريخ الالتقاط يجب أن يكون اليوم أو قبله'
                : 'The taken date must be today or earlier',
        ];
    }
}
