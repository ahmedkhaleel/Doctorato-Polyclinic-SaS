<?php

namespace App\Http\Requests\Dental;

use App\Models\DentalChart;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDentalChartRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'condition' => 'required|string|in:' . implode(',', DentalChart::CONDITIONS),
            'surfaces' => 'nullable|array',
            'surfaces.*' => 'string|in:' . implode(',', DentalChart::SURFACES),
            'notes' => 'nullable|string|max:500',
            'status' => 'nullable|string|in:present,missing,implant',
        ];
    }

    public function attributes(): array
    {
        if (app()->getLocale() === 'ar') {
            return [
                'condition' => 'الحالة',
                'surfaces' => 'الأسطح',
                'notes' => 'ملاحظات',
                'status' => 'حالة السن',
            ];
        }

        return [];
    }
}
