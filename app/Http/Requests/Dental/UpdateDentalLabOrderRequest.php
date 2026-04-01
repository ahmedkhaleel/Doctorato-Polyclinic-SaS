<?php

namespace App\Http\Requests\Dental;

use App\Models\DentalChart;
use App\Models\DentalLabOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDentalLabOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validTeeth = array_merge(DentalChart::ALL_TEETH, DentalChart::ALL_DECIDUOUS_TEETH);

        return [
            'lab_name' => 'nullable|string|max:255',
            'order_number' => 'nullable|string|max:100',
            'item_type' => ['nullable', 'string', Rule::in(DentalLabOrder::ITEM_TYPES)],
            'tooth_number' => ['nullable', 'integer', Rule::in($validTeeth)],
            'shade' => 'nullable|string|max:20',
            'material' => ['nullable', 'string', Rule::in(DentalLabOrder::MATERIALS)],
            'cost' => 'nullable|numeric|min:0|max:999999.99',
            'patient_charge' => 'nullable|numeric|min:0|max:999999.99',
            'status' => ['nullable', 'string', Rule::in(['ordered', 'in_production', 'ready', 'delivered', 'adjustment', 'completed'])],
            'expected_date' => 'nullable|date',
            'delivered_date' => 'nullable|date',
            'notes' => 'nullable|string|max:5000',
            'special_instructions' => 'nullable|string|max:5000',
        ];
    }

    public function attributes(): array
    {
        if (app()->getLocale() === 'ar') {
            return [
                'lab_name' => 'اسم المعمل',
                'item_type' => 'نوع العنصر',
                'tooth_number' => 'رقم السن',
                'shade' => 'اللون',
                'material' => 'المادة',
                'cost' => 'التكلفة',
                'patient_charge' => 'تكلفة المريض',
                'status' => 'الحالة',
                'expected_date' => 'التاريخ المتوقع',
                'delivered_date' => 'تاريخ التسليم',
            ];
        }

        return [];
    }
}
