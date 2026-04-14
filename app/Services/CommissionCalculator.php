<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorServiceRate;
use App\Models\Service;
use App\Models\Setting;
use App\Models\Visit;

class CommissionCalculator
{
    /**
     * Calculate doctor commission for a completed visit.
     *
     * Formula: (paid_amount - supply_cost) × commission_rate%
     *
     * Commission rate priority:
     * For services:
     *   1. doctor_service_rates (custom per doctor+service)
     *   2. doctors.default_commission_percentage
     *
     * For consultations:
     *   - dermatology → doctors.dermatology_commission (fallback: default_commission_percentage)
     *   - cosmetic → doctors.cosmetic_commission (fallback: default_commission_percentage)
     */
    public function calculate(Visit $visit, float $paidAmount): array
    {
        $doctor = $visit->doctor;
        $service = $visit->service;

        if (! $doctor) {
            return [
                'supply_cost' => 0,
                'commission_rate' => 0,
                'commission_base' => $paidAmount,
                'commission_amount' => 0,
            ];
        }

        // Handle consultation visits (no service linked)
        if (! $service) {
            return $this->calculateForConsultation($doctor, $paidAmount, $visit->consultation_type);
        }

        // Get supply cost from service
        $supplyCost = (float) ($service->supply_cost ?? 0);

        // Commission base = medical_fee (or price - supply_cost as fallback)
        $medicalFee = $service->medical_fee !== null
            ? (float) $service->medical_fee
            : max(0, $paidAmount - $supplyCost);

        // Determine commission rate (priority cascade)
        $rate = $this->getCommissionRate($doctor, $service);

        // Calculate commission on medical fee only
        $commission = round($medicalFee * $rate / 100, 2);

        return [
            'supply_cost' => $supplyCost,
            'medical_fee' => $medicalFee,
            'commission_rate' => $rate,
            'commission_base' => $medicalFee,
            'commission_amount' => $commission,
        ];
    }

    /**
     * Calculate commission for consultation visits (no service linked).
     * Uses dermatology/cosmetic-specific commission rates with fallback to default.
     */
    protected function calculateForConsultation(Doctor $doctor, float $paidAmount, ?string $consultationType = null): array
    {
        $rate = $this->getConsultationCommissionRate($doctor, $consultationType);
        $commission = round($paidAmount * $rate / 100, 2);

        return [
            'supply_cost' => 0,
            'commission_rate' => $rate,
            'commission_base' => $paidAmount,
            'commission_amount' => $commission,
        ];
    }

    /**
     * Get the consultation commission rate based on type.
     */
    public function getConsultationCommissionRate(Doctor $doctor, ?string $consultationType = null): float
    {
        if ($consultationType === 'dermatology' && $doctor->dermatology_commission !== null) {
            return (float) $doctor->dermatology_commission;
        }

        if ($consultationType === 'cosmetic' && $doctor->cosmetic_commission !== null) {
            return (float) $doctor->cosmetic_commission;
        }

        if ($consultationType === 'pediatric' && $doctor->pediatric_consultation_commission !== null) {
            return (float) $doctor->pediatric_consultation_commission;
        }

        if ($consultationType === 'dental' && $doctor->dental_consultation_commission !== null) {
            return (float) $doctor->dental_consultation_commission;
        }

        return (float) ($doctor->default_commission_percentage ?? 0);
    }

    /**
     * Get the consultation fee based on type and doctor_type.
     * Uses global Settings pricing, with per-doctor fee as override.
     */
    public function getConsultationFee(Doctor $doctor, ?string $consultationType = null): float
    {
        if ($consultationType === 'dermatology') {
            // Per-doctor override first
            if ($doctor->dermatology_fee !== null && (float) $doctor->dermatology_fee > 0) {
                return (float) $doctor->dermatology_fee;
            }
            // Settings-based pricing by doctor_type
            if ($doctor->doctor_type === 'consultant') {
                return (float) Setting::get('dermatology_consultant_fee', 0);
            }
            return (float) Setting::get('dermatology_specialist_fee', 0);
        }

        if ($consultationType === 'cosmetic') {
            // Per-doctor override first
            if ($doctor->cosmetic_fee !== null && (float) $doctor->cosmetic_fee > 0) {
                return (float) $doctor->cosmetic_fee;
            }
            return (float) Setting::get('cosmetic_consultation_fee', 0);
        }

        if ($consultationType === 'pediatric') {
            // Per-doctor override first
            if ($doctor->pediatric_consultation_fee !== null && (float) $doctor->pediatric_consultation_fee > 0) {
                return (float) $doctor->pediatric_consultation_fee;
            }
            return (float) Setting::get('pediatric_consultation_fee', 0);
        }

        if ($consultationType === 'dental') {
            // Per-doctor override first
            if ($doctor->dental_consultation_fee !== null && (float) $doctor->dental_consultation_fee > 0) {
                return (float) $doctor->dental_consultation_fee;
            }
            return (float) \App\Services\ModuleManager::getSetting('dental', 'consultation_fee', 300);
        }

        return (float) ($doctor->consultation_fee ?? 0);
    }

    /**
     * Get the commission rate for a doctor+service combination.
     */
    public function getCommissionRate(Doctor $doctor, Service $service): float
    {
        // 1. Check custom rate for this doctor+service
        $customRate = DoctorServiceRate::where('doctor_id', $doctor->id)
            ->where('service_id', $service->id)
            ->value('commission_percentage');

        if ($customRate !== null) {
            return (float) $customRate;
        }

        // 2. Fall back to doctor default
        return (float) ($doctor->default_commission_percentage ?? 0);
    }
}
