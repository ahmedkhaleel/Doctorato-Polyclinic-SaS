<?php

namespace App\Http\Requests\Dental;

use Illuminate\Foundation\Http\FormRequest;

class SignConsentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'signature' => ['required', 'string'], // base64 image data
            'action' => ['required', 'in:sign,decline'],
            'declined_reason' => ['nullable', 'required_if:action,decline', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'signature.required' => 'التوقيع مطلوب / Signature is required',
            'action.required' => 'الإجراء مطلوب / Action is required',
            'declined_reason.required_if' => 'يرجى ذكر سبب الرفض / Please provide a reason for declining',
        ];
    }
}
