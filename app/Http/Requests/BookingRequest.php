<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
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
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'module' => ['nullable', 'string', 'in:derma,dental,pediatric,obgyn,psychiatry,neurology'],
            'booking_type' => ['required', 'in:dermatology_consultation,cosmetic_consultation,service,dental_consultation,pediatric_consultation,pediatric_service,obgyn_consultation,obgyn_service,psychiatry_consultation,psychiatry_service,neurology_consultation,neurology_service,physiotherapy_consultation,physiotherapy_session,package_bundle'],
            'service_id' => ['nullable', 'required_if:booking_type,service', 'exists:services,id'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'preferred_date' => ['nullable', 'date', 'after_or_equal:today'],
            'preferred_time' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'privacy_consent' => ['required', 'accepted'],
            '_honeypot' => ['nullable', 'max:0'],
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
            // Full Name
            'full_name.required' => __('validation.custom.full_name.required', [], app()->getLocale()),
            'full_name.string' => __('validation.custom.full_name.string', [], app()->getLocale()),
            'full_name.max' => __('validation.custom.full_name.max', [], app()->getLocale()),

            // Phone
            'phone.required' => __('validation.custom.phone.required', [], app()->getLocale()),
            'phone.string' => __('validation.custom.phone.string', [], app()->getLocale()),
            'phone.max' => __('validation.custom.phone.max', [], app()->getLocale()),

            // Email
            'email.email' => __('validation.custom.email.email', [], app()->getLocale()),
            'email.max' => __('validation.custom.email.max', [], app()->getLocale()),

            // Booking Type
            'booking_type.required' => __('validation.custom.booking_type.required', [], app()->getLocale()),
            'booking_type.in' => __('validation.custom.booking_type.in', [], app()->getLocale()),

            // Service
            'service_id.required_if' => __('validation.custom.service_id.required_if', [], app()->getLocale()),
            'service_id.exists' => __('validation.custom.service_id.exists', [], app()->getLocale()),

            // Doctor
            'doctor_id.exists' => __('validation.custom.doctor_id.exists', [], app()->getLocale()),

            // Preferred Date
            'preferred_date.date' => __('validation.custom.preferred_date.date', [], app()->getLocale()),
            'preferred_date.after_or_equal' => __('validation.custom.preferred_date.after_or_equal', [], app()->getLocale()),

            // Preferred Time
            'preferred_time.string' => __('validation.custom.preferred_time.string', [], app()->getLocale()),

            // Notes
            'notes.string' => __('validation.custom.notes.string', [], app()->getLocale()),
            'notes.max' => __('validation.custom.notes.max', [], app()->getLocale()),

            // Privacy Consent
            'privacy_consent.required' => __('validation.custom.privacy_consent.required', [], app()->getLocale()),
            'privacy_consent.accepted' => __('validation.custom.privacy_consent.accepted', [], app()->getLocale()),
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
                'full_name' => 'الاسم الكامل',
                'phone' => 'رقم الهاتف',
                'email' => 'البريد الإلكتروني',
                'booking_type' => 'نوع الحجز',
                'service_id' => 'الخدمة',
                'doctor_id' => 'الطبيب',
                'preferred_date' => 'التاريخ المفضل',
                'preferred_time' => 'الوقت المفضل',
                'notes' => 'ملاحظات',
                'privacy_consent' => 'الموافقة على سياسة الخصوصية',
            ];
        }

        return [
            'full_name' => 'full name',
            'phone' => 'phone number',
            'email' => 'email address',
            'booking_type' => 'booking type',
            'service_id' => 'service',
            'doctor_id' => 'doctor',
            'preferred_date' => 'preferred date',
            'preferred_time' => 'preferred time',
            'notes' => 'notes',
            'privacy_consent' => 'privacy consent',
        ];
    }
}
