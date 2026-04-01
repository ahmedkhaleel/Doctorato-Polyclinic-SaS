<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $patientId = $this->route('patient')?->id;

        return [
            'full_name' => 'required|string|max:255',
            'phone' => [
                'required', 'string', 'max:20',
                'regex:/^[0-9+\-\s]+$/',
                Rule::unique('patients', 'phone')
                    ->ignore($patientId)
                    ->whereNull('deleted_at'),
            ],
            'phone2' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'gender' => 'required|in:male,female',
            'blood_type' => 'nullable|string|in:A+,A-,B+,B-,AB+,AB-,O+,O-',
            'marital_status' => 'nullable|string|in:single,married,divorced,widowed',
            'nationality' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'occupation' => 'nullable|string|max:100',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9+\-\s]+$/'],
            'allergies' => 'nullable|string|max:1000',
            'chronic_conditions' => 'nullable|string|max:1000',
            'current_medications' => 'nullable|string|max:1000',
            'referral_source' => 'nullable|in:walk_in,social_media,google,friend,doctor,advertisement,other',
            'referred_by' => 'nullable|string|max:255',
            'medical_notes' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone must contain only numbers, +, - and spaces.',
            'phone2.regex' => 'Phone must contain only numbers, +, - and spaces.',
            'emergency_contact_phone.regex' => 'Emergency phone must contain only numbers.',
            'date_of_birth.before' => 'Date of birth must be in the past.',
            'photo.max' => 'Photo must not exceed 5MB.',
        ];
    }
}
