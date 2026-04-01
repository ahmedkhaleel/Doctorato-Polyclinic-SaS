<?php

namespace App\Http\Resources\Dental;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DentalTreatmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'visit_id' => $this->visit_id,
            'invoice_id' => $this->invoice_id,
            'treatment_plan_id' => $this->treatment_plan_id,
            'tooth_number' => $this->tooth_number,
            'treatment_type' => $this->treatment_type,
            'surfaces' => $this->surfaces,
            'description' => $this->description,
            'cost' => (float) $this->cost,
            'lab_cost' => (float) $this->lab_cost,
            'total_cost' => (float) $this->total_cost,
            'status' => $this->status,
            'completed_at' => $this->completed_at?->toDateString(),
            'notes' => $this->notes,
            'followup_reminder_at' => $this->followup_reminder_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Relations (loaded conditionally)
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
            'treatment_plan' => $this->whenLoaded('treatmentPlan', fn () => [
                'id' => $this->treatmentPlan->id,
                'title_ar' => $this->treatmentPlan->title_ar,
                'title_en' => $this->treatmentPlan->title_en,
            ]),
            'lab_order' => new DentalLabOrderResource($this->whenLoaded('labOrder')),
            'prescription' => $this->whenLoaded('prescription'),
        ];
    }
}
