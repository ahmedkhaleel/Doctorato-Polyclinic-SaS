<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     * Converts Arabic/Persian numerals to English in the phone field.
     */
    protected function prepareForValidation(): void
    {
        if ($this->phone) {
            $this->merge([
                'phone' => $this->toEnglishNumbers($this->phone),
            ]);
        }
    }

    /**
     * Convert Arabic (٠-٩) and Persian (۰-۹) numerals to English (0-9).
     */
    private function toEnglishNumbers(string $str): string
    {
        $arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $persian = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
        $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        return str_replace($persian, $english, str_replace($arabic, $english, $str));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:255'],
            'email'   => ['nullable', 'email', 'max:255'],
            'phone'   => ['nullable', 'string', 'max:20'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    /**
     * Get custom validation messages in Arabic and English.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Name
            'name.required' => __('validation.custom.name.required', [], app()->getLocale()),
            'name.string'   => __('validation.custom.name.string', [], app()->getLocale()),
            'name.max'      => __('validation.custom.name.max', [], app()->getLocale()),

            // Email
            'email.email'   => __('validation.custom.email.email', [], app()->getLocale()),
            'email.max'     => __('validation.custom.email.max', [], app()->getLocale()),

            // Phone
            'phone.string'  => __('validation.custom.phone.string', [], app()->getLocale()),
            'phone.max'     => __('validation.custom.phone.max', [], app()->getLocale()),

            // Subject
            'subject.string' => __('validation.custom.subject.string', [], app()->getLocale()),
            'subject.max'    => __('validation.custom.subject.max', [], app()->getLocale()),

            // Message
            'message.required' => __('validation.custom.message.required', [], app()->getLocale()),
            'message.string'   => __('validation.custom.message.string', [], app()->getLocale()),
            'message.max'      => __('validation.custom.message.max', [], app()->getLocale()),
        ];
    }

    /**
     * Get custom attribute names for error messages.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $locale = app()->getLocale();

        if ($locale === 'ar') {
            return [
                'name'    => 'الاسم',
                'email'   => 'البريد الإلكتروني',
                'phone'   => 'رقم الهاتف',
                'subject' => 'الموضوع',
                'message' => 'الرسالة',
            ];
        }

        return [
            'name'    => 'name',
            'email'   => 'email address',
            'phone'   => 'phone number',
            'subject' => 'subject',
            'message' => 'message',
        ];
    }
}
