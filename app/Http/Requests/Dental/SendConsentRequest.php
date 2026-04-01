<?php

namespace App\Http\Requests\Dental;

use Illuminate\Foundation\Http\FormRequest;

class SendConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'risks_notes' => ['nullable', 'string', 'max:2000'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }

    public function attributes(): array
    {
        return [
            'risks_notes' => 'ملاحظات المخاطر / Risks Notes',
            'expires_in_days' => 'مدة الصلاحية / Expiry Days',
        ];
    }
}
