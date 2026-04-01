<?php

namespace App\Http\Requests\Dental;

use App\Models\DentalChart;
use App\Models\DentalLabOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDentalLabOrderRequest extends FormRequest
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
            'doctor_id' => 'required|exists:doctors,id',
            'dental_treatment_id' => 'nullable|exists:dental_treatments,id',
            'lab_name' => 'nullable|string|max:255',
            'order_number' => 'nullable|string|max:100',
            'item_type' => ['required', 'string', Rule::in(DentalLabOrder::ITEM_TYPES)],
            'tooth_number' => ['nullable', 'integer', Rule::in($validTeeth)],
            'shade' => 'nullable|string|max:20',
            'material' => ['nullable', 'string', Rule::in(DentalLabOrder::MATERIALS)],
            'cost' => 'nullable|numeric|min:0|max:999999.99',
            'patient_charge' => 'nullable|numeric|min:0|max:999999.99',
            'order_date' => 'required|date',
            'expected_date' => 'nullable|date|after_or_equal:order_date',
            'notes' => 'nullable|string|max:5000',
            'special_instructions' => 'nullable|string|max:5000',
        ];
    }

    public function attributes(): array
    {
        if (app()->getLocale() === 'ar') {
            return [
                'patient_id' => 'المريض',
                'doctor_id' => 'الطبيب',
                'lab_name' => 'اسم المعمل',
                'item_type' => 'نوع العنصر',
                'tooth_number' => 'رقم السن',
                'shade' => 'اللون',
                'material' => 'المادة',
                'cost' => 'التكلفة',
                'patient_charge' => 'تكلفة المريض',
                'order_date' => 'تاريخ الطلب',
                'expected_date' => 'التاريخ المتوقع',
            ];
        }

        return [];
    }
}
