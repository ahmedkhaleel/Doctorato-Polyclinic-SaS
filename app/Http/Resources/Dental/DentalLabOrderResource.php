<?php

namespace App\Http\Resources\Dental;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DentalLabOrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'doctor_id' => $this->doctor_id,
            'dental_treatment_id' => $this->dental_treatment_id,
            'invoice_item_id' => $this->invoice_item_id,
            'lab_name' => $this->lab_name,
            'order_number' => $this->order_number,
            'item_type' => $this->item_type,
            'tooth_number' => $this->tooth_number,
            'shade' => $this->shade,
            'material' => $this->material,
            'cost' => (float) $this->cost,
            'patient_charge' => (float) $this->patient_charge,
            'status' => $this->status,
            'order_date' => $this->order_date,
            'expected_date' => $this->expected_date,
            'delivered_date' => $this->delivered_date,
            'notes' => $this->notes,
            'special_instructions' => $this->special_instructions,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

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
            'treatment' => new DentalTreatmentResource($this->whenLoaded('treatment')),
        ];
    }
}
