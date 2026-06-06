<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorServiceRate;
use App\Models\Service;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * P5-3 — per-doctor service commission rates. CommissionCalculator already
 * prefers DoctorServiceRate over the doctor's default_commission_percentage;
 * this gives admins a screen to SET those per-service rates (and bulk-apply a
 * value to all services), instead of editing the pivot by hand. Setting a rate
 * to blank removes the override (falls back to the default).
 */
class DoctorServiceRateController extends Controller
{
    public function index(Doctor $doctor): Response
    {
        $rates = DoctorServiceRate::where('doctor_id', $doctor->id)
            ->pluck('commission_percentage', 'service_id');

        $services = Service::query()
            ->when($doctor->module, fn ($q) => $q->where('module', $doctor->module))
            ->orderBy('module')->orderBy('name_en')
            ->get(['id', 'name_ar', 'name_en', 'module', 'doctor_commission_percentage'])
            ->map(fn (Service $s) => [
                'id' => $s->id,
                'name_ar' => $s->name_ar,
                'name_en' => $s->name_en,
                'module' => $s->module,
                'service_default' => $s->doctor_commission_percentage !== null ? (float) $s->doctor_commission_percentage : null,
                'rate' => isset($rates[$s->id]) ? (float) $rates[$s->id] : null,
            ]);

        return Inertia::render('Admin/Doctors/ServiceRates', [
            'doctor' => [
                'id' => $doctor->id,
                'name_ar' => $doctor->name_ar,
                'name_en' => $doctor->name_en,
                'module' => $doctor->module,
                'default_commission_percentage' => (float) $doctor->default_commission_percentage,
            ],
            'services' => $services,
        ]);
    }

    public function update(Request $request, Doctor $doctor): RedirectResponse
    {
        $data = $request->validate([
            'rates' => 'array',
            'rates.*' => 'nullable|numeric|min:0|max:100',
        ]);

        $serviceIds = Service::query()
            ->when($doctor->module, fn ($q) => $q->where('module', $doctor->module))
            ->pluck('id')->all();

        foreach (($data['rates'] ?? []) as $serviceId => $value) {
            $serviceId = (int) $serviceId;
            if (! in_array($serviceId, $serviceIds, true)) {
                continue; // ignore services outside the doctor's module
            }

            if ($value === null || $value === '') {
                // Blank → remove the override (fall back to default).
                DoctorServiceRate::where('doctor_id', $doctor->id)->where('service_id', $serviceId)->delete();

                continue;
            }

            DoctorServiceRate::updateOrCreate(
                ['doctor_id' => $doctor->id, 'service_id' => $serviceId],
                ['commission_percentage' => round((float) $value, 2)],
            );
        }

        AuditLogger::log('updated', $doctor, null, 'Updated per-service commission rates');

        return back()->with('success', app()->getLocale() === 'ar' ? 'تم حفظ نسب العمولة.' : 'Commission rates saved.');
    }

    /** Bulk-apply one percentage to every service in the doctor's module. */
    public function bulkApply(Request $request, Doctor $doctor): RedirectResponse
    {
        $data = $request->validate([
            'percentage' => 'required|numeric|min:0|max:100',
        ]);

        $serviceIds = Service::query()
            ->when($doctor->module, fn ($q) => $q->where('module', $doctor->module))
            ->pluck('id');

        foreach ($serviceIds as $serviceId) {
            DoctorServiceRate::updateOrCreate(
                ['doctor_id' => $doctor->id, 'service_id' => (int) $serviceId],
                ['commission_percentage' => round((float) $data['percentage'], 2)],
            );
        }

        AuditLogger::log('updated', $doctor, ['percentage' => $data['percentage']], 'Bulk-applied service commission rate');

        return back()->with('success', app()->getLocale() === 'ar' ? 'تم تطبيق النسبة على كل الخدمات.' : 'Rate applied to all services.');
    }
}
