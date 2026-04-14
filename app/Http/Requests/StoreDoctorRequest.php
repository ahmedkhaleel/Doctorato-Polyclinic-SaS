<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'module' => 'nullable|string|in:derma,dental',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:20480',
            'specialization_ar' => 'required|string|max:255',
            'specialization_en' => 'required|string|max:255',
            'bio_ar' => 'nullable|string',
            'bio_en' => 'nullable|string',
            'qualifications_ar' => 'nullable|string',
            'qualifications_en' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'doctor_type' => 'nullable|in:consultant,specialist',
            // Clinic fields
            'user_id' => 'nullable|exists:users,id',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'consultation_fee' => 'nullable|numeric|min:0',
            'dermatology_fee' => 'nullable|numeric|min:0',
            'cosmetic_fee' => 'nullable|numeric|min:0',
            'dental_consultation_fee' => 'nullable|numeric|min:0',
            'dental_service_fee' => 'nullable|numeric|min:0',
            'default_commission_percentage' => 'nullable|numeric|min:0|max:100',
            'dermatology_commission' => 'nullable|numeric|min:0|max:100',
            'cosmetic_commission' => 'nullable|numeric|min:0|max:100',
            'followup_commission' => 'nullable|numeric|min:0|max:100',
            'dental_consultation_commission' => 'nullable|numeric|min:0|max:100',
            'dental_service_commission' => 'nullable|numeric|min:0|max:100',
            'pediatric_consultation_commission' => 'nullable|numeric|min:0|max:100',
            'pediatric_followup_commission' => 'nullable|numeric|min:0|max:100',
            'clinic_notes' => 'nullable|string',
            // Auto-create user account
            'create_user_account' => 'nullable|boolean',
            'create_user_password' => 'nullable|required_if:create_user_account,true|string|min:6',
            // Nested: schedules, vacations, service_rates
            'schedules' => 'nullable|array',
            'schedules.*.day_of_week' => 'required_with:schedules|integer|min:0|max:6',
            'schedules.*.start_time' => 'required_with:schedules|string',
            'schedules.*.end_time' => 'required_with:schedules|string',
            'schedules.*.is_active' => 'nullable',
            'vacations' => 'nullable|array',
            'vacations.*.start_date' => 'required_with:vacations|date',
            'vacations.*.end_date' => 'required_with:vacations|date',
            'vacations.*.reason' => 'nullable|string',
            'service_rates' => 'nullable|array',
            'service_rates.*.service_id' => 'required_with:service_rates|exists:services,id',
            'service_rates.*.commission_percentage' => 'required_with:service_rates|numeric|min:0|max:100',
        ];
    }
}
