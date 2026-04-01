<?php

namespace App\Services;

use App\Models\DentalPrescriptionTemplate;
use App\Models\DentalTreatment;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use App\Models\User;
use App\Notifications\DentalPrescriptionAutoCreatedNotification;
use Illuminate\Support\Facades\DB;

class DentalPrescriptionService
{
    /**
     * Auto-generate prescription for a completed dental treatment
     * using matching auto_apply templates.
     */
    public function generateForCompletedTreatment(DentalTreatment $treatment): ?Prescription
    {
        if ($treatment->status !== 'completed') {
            return null;
        }

        // Skip if prescription already exists for this treatment
        if ($treatment->prescription) {
            return $treatment->prescription;
        }

        // Find auto-apply templates for this treatment type
        $templates = DentalPrescriptionTemplate::active()
            ->autoApply()
            ->forTreatmentType($treatment->treatment_type)
            ->with('items')
            ->orderBy('sort_order')
            ->get();

        if ($templates->isEmpty()) {
            return null;
        }

        return DB::transaction(function () use ($treatment, $templates) {
            // Merge all template items into one prescription
            $firstTemplate = $templates->first();

            $prescription = Prescription::create([
                'patient_id' => $treatment->patient_id,
                'doctor_id' => $treatment->doctor_id,
                'visit_id' => $treatment->visit_id,
                'dental_treatment_id' => $treatment->id,
                'diagnosis' => $this->buildDiagnosis($treatment, $firstTemplate),
                'notes' => $this->buildNotes($templates),
            ]);

            $sortOrder = 0;
            foreach ($templates as $template) {
                foreach ($template->items as $item) {
                    PrescriptionItem::create([
                        'prescription_id' => $prescription->id,
                        'medication_name' => $item->medication_name,
                        'dosage' => $item->dosage,
                        'frequency' => $item->frequency,
                        'duration' => $item->duration,
                        'instructions' => $item->instructions_ar,
                        'sort_order' => $sortOrder++,
                    ]);
                }
            }

            AuditLogger::log('created', $prescription, null,
                "Auto-generated prescription for {$treatment->treatment_type} treatment"
                . ($treatment->tooth_number ? " (tooth #{$treatment->tooth_number})" : '')
            );

            // Notify the treating doctor to review the auto-prescription
            $doctorUser = User::whereHas('doctor', fn ($q) => $q->where('id', $treatment->doctor_id))
                ->where('is_active', true)
                ->first();
            if ($doctorUser) {
                $doctorUser->notify(new DentalPrescriptionAutoCreatedNotification($prescription, $treatment));
            }

            return $prescription->load('items');
        });
    }

    /**
     * Generate prescription from a specific template (manual apply by doctor).
     */
    public function generateFromTemplate(
        DentalPrescriptionTemplate $template,
        int $patientId,
        int $doctorId,
        ?int $visitId = null,
        ?int $dentalTreatmentId = null
    ): Prescription {
        return DB::transaction(function () use ($template, $patientId, $doctorId, $visitId, $dentalTreatmentId) {
            $prescription = Prescription::create([
                'patient_id' => $patientId,
                'doctor_id' => $doctorId,
                'visit_id' => $visitId,
                'dental_treatment_id' => $dentalTreatmentId,
                'diagnosis' => $template->diagnosis_ar,
                'notes' => $template->notes_ar,
            ]);

            foreach ($template->items as $index => $item) {
                PrescriptionItem::create([
                    'prescription_id' => $prescription->id,
                    'medication_name' => $item->medication_name,
                    'dosage' => $item->dosage,
                    'frequency' => $item->frequency,
                    'duration' => $item->duration,
                    'instructions' => $item->instructions_ar,
                    'sort_order' => $index,
                ]);
            }

            AuditLogger::log('created', $prescription, null,
                "Prescription created from template \"{$template->name_ar}\""
            );

            return $prescription->load('items');
        });
    }

    /**
     * Get available templates for a treatment type (for doctor UI).
     */
    public function getTemplatesForType(string $treatmentType): \Illuminate\Database\Eloquent\Collection
    {
        return DentalPrescriptionTemplate::active()
            ->forTreatmentType($treatmentType)
            ->with('items')
            ->orderBy('sort_order')
            ->get();
    }

    protected function buildDiagnosis(DentalTreatment $treatment, DentalPrescriptionTemplate $template): string
    {
        $toothLabel = $treatment->tooth_number ? " (سن #{$treatment->tooth_number})" : '';
        $typeAr = $this->getArabicType($treatment->treatment_type);

        if ($template->diagnosis_ar) {
            return $template->diagnosis_ar . $toothLabel;
        }

        return "وصفة بعد {$typeAr}{$toothLabel}";
    }

    protected function buildNotes($templates): ?string
    {
        $notes = $templates
            ->pluck('notes_ar')
            ->filter()
            ->unique()
            ->implode("\n");

        return $notes ?: null;
    }

    protected function getArabicType(string $type): string
    {
        return match ($type) {
            'filling' => 'حشو',
            'extraction' => 'خلع',
            'root_canal' => 'علاج عصب',
            'crown' => 'تركيب تاج',
            'bridge' => 'تركيب جسر',
            'implant' => 'زراعة',
            'cleaning' => 'تنظيف',
            'scaling' => 'تقليح',
            'whitening' => 'تبييض',
            'veneer' => 'قشرة',
            'orthodontic' => 'تقويم',
            'denture' => 'طقم أسنان',
            'sealant' => 'حشو وقائي',
            'fluoride' => 'فلورايد',
            'gum_treatment' => 'علاج لثة',
            'surgical_extraction' => 'خلع جراحي',
            'bone_graft' => 'ترقيع عظم',
            'sinus_lift' => 'رفع جيب أنفي',
            'night_guard' => 'واقي ليلي',
            'retainer' => 'مثبت',
            default => 'علاج أسنان',
        };
    }
}
