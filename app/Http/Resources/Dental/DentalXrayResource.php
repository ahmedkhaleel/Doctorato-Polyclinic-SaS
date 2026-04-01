<?php

namespace App\Http\Resources\Dental;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DentalXrayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'type' => $this->type,
            'image_path' => $this->image_path,
            'image_url' => $this->image_path ? asset('storage/' . $this->image_path) : null,
            'tooth_number' => $this->tooth_number,
            'findings' => $this->findings,
            'notes' => $this->notes,
            'taken_date' => $this->taken_date,
            'created_at' => $this->created_at?->toISOString(),

            'patient' => $this->whenLoaded('patient', fn () => [
                'id' => $this->patient->id,
                'full_name' => $this->patient->full_name,
                'file_number' => $this->patient->file_number,
            ]),
            'doctor' => $this->whenLoaded('doctor', fn () => [
                'id' => $this->doctor->id,
                'name_ar' => $this->doctor->name_ar,
                'name_en' => $this->doctor->name_en,
            ]),
        ];
    }
}
