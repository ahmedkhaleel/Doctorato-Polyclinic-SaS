<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isService = in_array($this->input('booking_type'), ['service', 'dental_service', 'pediatric_service', 'obgyn_service', 'psychiatry_service', 'neurology_service']);

        return [
            'patient_id' => 'required|exists:patients,id',
            'booking_type' => 'required|in:dermatology_consultation,cosmetic_consultation,dental_consultation,dental_service,pediatric_consultation,pediatric_service,obgyn_consultation,obgyn_service,psychiatry_consultation,psychiatry_service,neurology_consultation,neurology_service,service',
            'notes' => 'nullable|string|max:1000',
            'services' => 'required|array|min:1',
            'services.*.service_id' => $isService ? 'required|exists:services,id' : 'nullable',
            'services.*.doctor_id' => 'nullable|exists:doctors,id',
            'services.*.sessions_count' => 'required|integer|min:1|max:50',
            'services.*.unit_price' => 'required|numeric|min:0',
            'services.*.discount_per_session' => 'nullable|numeric|min:0',
            'services.*.notes' => 'nullable|string|max:500',
            'services.*.appointments' => 'required|array|min:1',
            'services.*.appointments.*.doctor_id' => 'nullable|exists:doctors,id',
            'services.*.appointments.*.date' => 'required|date|after_or_equal:today',
            'services.*.appointments.*.start_time' => 'required|date_format:H:i',
            'services.*.appointments.*.end_time' => 'required|date_format:H:i|after:services.*.appointments.*.start_time',
        ];
    }
}
