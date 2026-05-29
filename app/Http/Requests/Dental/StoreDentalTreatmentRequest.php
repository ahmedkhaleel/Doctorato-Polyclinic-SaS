<?php

namespace App\Http\Requests\Dental;

use App\Models\DentalChart;
use App\Models\DentalTreatment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDentalTreatmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Valid FDI tooth numbers: adult (11-48) + deciduous (51-85)
        $validTeeth = array_merge(DentalChart::ALL_TEETH, DentalChart::ALL_DECIDUOUS_TEETH);

        return [
            'patient_id' => 'required|exists:patients,id',
            'doctor_id' => 'nullable|exists:doctors,id',
            'visit_id' => 'nullable|exists:visits,id',
            'treatment_plan_id' => 'nullable|exists:dental_treatment_plans,id',
            'tooth_number' => ['nullable', 'integer', Rule::in($validTeeth)],
            'treatment_type' => ['required', 'string', Rule::in(DentalTreatment::TYPES)],
            'surfaces' => 'nullable|array',
            'surfaces.*' => ['string', Rule::in(DentalChart::SURFACES)],
            'description' => 'nullable|string|max:2000',
            'cost' => 'nullable|numeric|min:0|max:999999.99',
            'lab_cost' => 'nullable|numeric|min:0|max:999999.99',
            'supply_id' => 'nullable|exists:supplies,id',
            'consumption_qty' => 'nullable|numeric|min:0',
            'status' => ['nullable', 'string', Rule::in(['planned', 'in_progress', 'completed', 'cancelled'])],
            'notes' => 'nullable|string|max:5000',
        ];
    }

    public function attributes(): array
    {
        if (app()->getLocale() === 'ar') {
            return [
                'patient_id' => 'المريض',
                'doctor_id' => 'الطبيب',
                'tooth_number' => 'رقم السن',
                'treatment_type' => 'نوع العلاج',
                'surfaces' => 'الأسطح',
                'cost' => 'التكلفة',
                'lab_cost' => 'تكلفة المعمل',
                'status' => 'الحالة',
            ];
        }

        return [];
    }
}
