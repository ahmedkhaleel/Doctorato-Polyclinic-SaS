<?php

namespace App\Http\Resources\Dental;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DentalTreatmentPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'title_ar' => $this->title_ar,
            'title_en' => $this->title_en,
            'description' => $this->description,
            'estimated_cost' => (float) $this->estimated_cost,
            'estimated_sessions' => (int) $this->estimated_sessions,
            'priority' => $this->priority,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'expected_end_date' => $this->expected_end_date,
            'completed_at' => $this->completed_at?->toDateString(),
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            'patient' => $this->whenLoaded('patient', fn () => [
                'id' => $this->patient->id,
                'full_name' => $this->patient->full_name,
                'file_number' => $this->patient->file_number,
                'phone' => $this->patient->phone,
            ]),
            'doctor' => $this->whenLoaded('doctor', fn () => [
                'id' => $this->doctor->id,
                'name_ar' => $this->doctor->name_ar,
                'name_en' => $this->doctor->name_en,
            ]),
            'treatments' => DentalTreatmentResource::collection($this->whenLoaded('treatments')),

            // Computed stats
            'treatments_count' => $this->whenCounted('treatments'),
            'completed_count' => $this->when(
                $this->relationLoaded('treatments'),
                fn () => $this->treatments->where('status', 'completed')->count()
            ),
            'actual_cost' => $this->when(
                $this->relationLoaded('treatments'),
                fn () => (float) $this->treatments->sum('cost')
            ),
        ];
    }
}
