<?php

namespace App\Listeners;

use App\Events\LeadConverted;
use App\Events\PatientRegistered;
use App\Models\Patient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CreatePatientFromLead implements ShouldQueue
{
    /**
     * When a lead is converted, automatically create a patient file
     * if one doesn't already exist for this contact.
     */
    public function handle(LeadConverted $event): void
    {
        $lead = $event->lead;

        // Check if patient already exists by phone or email
        $exists = Patient::where(function ($q) use ($lead) {
            if ($lead->phone) {
                $q->orWhere('phone', $lead->phone);
            }
            if ($lead->email) {
                $q->orWhere('email', $lead->email);
            }
        })->exists();

        if ($exists) {
            Log::info('Lead converted — patient already exists', ['lead_id' => $lead->id]);
            return;
        }

        $patient = new Patient([
            'full_name' => $lead->full_name ?? $lead->first_name . ' ' . $lead->last_name,
            'phone' => $lead->phone,
            'email' => $lead->email,
            'referral_source' => 'crm_lead',
        ]);
        $patient->file_number = Patient::generateFileNumber();
        $patient->is_active = true;
        $patient->save();

        // Update lead with patient link
        $lead->update(['patient_id' => $patient->id]);

        Log::info('Patient created from converted lead', [
            'lead_id' => $lead->id,
            'patient_id' => $patient->id,
            'file_number' => $patient->file_number,
        ]);

        // Fire PatientRegistered so welcome SMS + booking linking runs
        PatientRegistered::dispatch($patient, 'crm');
    }
}
