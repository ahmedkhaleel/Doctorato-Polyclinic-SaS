<?php

namespace App\Http\Resources\Dental;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DentalChartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'patient_id' => $this->patient_id,
            'tooth_number' => (int) $this->tooth_number,
            'condition' => $this->condition,
            'surfaces' => $this->surfaces,
            'status' => $this->status,
            'notes' => $this->notes,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
