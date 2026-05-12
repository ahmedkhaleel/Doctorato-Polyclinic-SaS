<?php

namespace App\Http\Controllers\Secretary;

use App\Models\Visit;
use App\Services\AuditLogger;
use App\Services\VisitWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryVisitController extends BaseSecretaryController
{
    public function __construct(
        protected VisitWorkflowService $workflowService,
    ) {}

    public function index(Request $request): Response
    {
        $query = Visit::with(['patient', 'doctor', 'service', 'booking']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })->orWhereHas('doctor', function ($dq) use ($search) {
                    $dq->where('name_ar', 'like', "%{$search}%")
                        ->orWhere('name_en', 'like', "%{$search}%");
                });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($visitType = $request->input('visit_type')) {
            $query->where('visit_type', $visitType);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('visit_date', '>=', $dateFrom);
        }

        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('visit_date', '<=', $dateTo);
        }

        if ($module = $request->input('module')) {
            $query->where('module', $module);
        }

        $visits = $query->latest('visit_date')->paginate(15)->withQueryString();

        return Inertia::render('Secretary/Visits/Index', [
            'visits' => $visits,
            'filters' => $request->only(['search', 'status', 'visit_type', 'date_from', 'date_to', 'module']),
        ]);
    }

    public function show(Visit $visit): Response
    {
        $visit->load([
            'patient',
            'doctor',
            'service',
            'receptionist',
            'booking',
            'bookingAppointment',
            'prescriptions.items',
            'prescriptions.doctor',
            'invoice.payments.paymentMethod',
            'invoice.items',
            'dentalTreatments',
        ]);

        $extra = [];

        if ($visit->module === 'dental' && $visit->patient_id) {
            $extra['dentalChart'] = \App\Models\DentalChart::where('patient_id', $visit->patient_id)
                ->orderBy('tooth_number')->get()->keyBy('tooth_number');
            $extra['dentalConditions'] = \App\Models\DentalChart::CONDITIONS;
            $extra['allTeeth'] = \App\Models\DentalChart::ALL_TEETH;
            $extra['treatmentTypes'] = \App\Models\DentalTreatment::TYPES;
        }

        // Doctors + services for the reschedule/reassign editor.
        $doctors = \App\Models\Doctor::active()
            ->where('module', $visit->module)
            ->select('id', 'name_ar', 'name_en')
            ->orderBy('name_en')
            ->get();
        $services = \App\Models\Service::active()
            ->where('module', $visit->module)
            ->select('id', 'name_ar', 'name_en', 'price')
            ->orderBy('name_en')
            ->get();

        return Inertia::render('Secretary/Visits/Show', array_merge([
            'visit' => $visit,
            'doctors' => $doctors,
            'services' => $services,
        ], $extra));
    }

    public function start(Visit $visit): RedirectResponse
    {
        $this->workflowService->start($visit);

        AuditLogger::log('started', $visit);

        return redirect()->back()->with('success', $this->msg('Visit started.', 'تم بدء الزيارة.'));
    }

    public function complete(Visit $visit): RedirectResponse
    {
        $results = $this->workflowService->complete($visit);

        AuditLogger::log('completed', $visit);

        $invoiceNum = $results['invoice'] ? $results['invoice']->invoice_number : null;
        $message = $this->msg(
            'Visit completed.' . ($invoiceNum ? " Invoice #{$invoiceNum} generated." : ''),
            'تم إكمال الزيارة.' . ($invoiceNum ? " تم إنشاء الفاتورة #{$invoiceNum}." : ''),
        );

        return redirect()->back()->with('success', $message);
    }

    public function cancel(Visit $visit): RedirectResponse
    {
        $this->workflowService->cancel($visit);

        AuditLogger::log('cancelled', $visit);

        return redirect()->back()->with('success', $this->msg('Visit cancelled.', 'تم إلغاء الزيارة.'));
    }

    /**
     * Edit the scheduling details of a visit — date, time, doctor,
     * or service. Used when a doctor is unavailable, the patient
     * wants a different provider, or the time slot needs to shift.
     * Mirrors Admin\VisitController::updateDetails so both portals
     * share the same edit surface.
     */
    public function updateDetails(Request $request, Visit $visit): RedirectResponse
    {
        if (in_array($visit->status, ['completed', 'cancelled'])) {
            return back()->with('error', $this->msg(
                'Completed or cancelled visits cannot be edited.',
                'لا يمكن تعديل الزيارات المكتملة أو الملغاة.',
            ));
        }

        $data = $request->validate([
            'visit_date'     => ['required', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'doctor_id'      => ['nullable', 'integer', 'exists:doctors,id'],
            'service_id'     => ['nullable', 'integer', 'exists:services,id'],
        ]);

        $changes = [];
        foreach ($data as $key => $newValue) {
            $oldValue = $visit->{$key};
            if ($key === 'visit_date' && $oldValue instanceof \DateTimeInterface) {
                $oldValue = $oldValue->format('Y-m-d');
            }
            if ((string) $oldValue !== (string) $newValue) {
                $changes[$key] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        // Hydrate FK display names for the patient email body
        if (isset($changes['doctor_id'])) {
            $fromId = $changes['doctor_id']['from'] ?? null;
            $toId   = $changes['doctor_id']['to']   ?? null;
            $doctors = \App\Models\Doctor::whereIn('id', array_filter([$fromId, $toId]))
                ->pluck('name_ar', 'id');
            $changes['doctor_id']['from_name'] = $fromId ? ($doctors[$fromId] ?? '—') : '—';
            $changes['doctor_id']['to_name']   = $toId   ? ($doctors[$toId]   ?? '—') : '—';
        }
        if (isset($changes['service_id'])) {
            $fromId = $changes['service_id']['from'] ?? null;
            $toId   = $changes['service_id']['to']   ?? null;
            $services = \App\Models\Service::whereIn('id', array_filter([$fromId, $toId]))
                ->pluck('name_ar', 'id');
            $changes['service_id']['from_name'] = $fromId ? ($services[$fromId] ?? '—') : '—';
            $changes['service_id']['to_name']   = $toId   ? ($services[$toId]   ?? '—') : '—';
        }

        $visit->update($data);

        AuditLogger::log('visit_details_updated', $visit, $changes);

        // Best-effort patient notification
        if ($changes) {
            $fresh = $visit->fresh(['patient', 'doctor', 'service']);
            $patient = $fresh->patient;
            if ($patient && $patient->email && $patient->wantsNotification('bookings', 'email')) {
                try {
                    \Illuminate\Support\Facades\Notification::route('mail', $patient->email)
                        ->notify(new \App\Notifications\VisitRescheduledEmail(
                            $fresh,
                            $changes,
                            $fresh->doctor?->name_ar ?? $fresh->doctor?->name_en,
                            $fresh->service?->name_ar ?? $fresh->service?->name_en,
                        ));
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning('[visit.rescheduled.email] failed', [
                        'visit_id' => $fresh->id,
                        'error'    => $e->getMessage(),
                    ]);
                }
            }
        }

        return redirect()->back()->with('success', $this->msg('Visit updated successfully.', 'تم تحديث الزيارة بنجاح.'));
    }

    /**
     * Restore a CANCELLED visit back to the active queue, optionally
     * with a fresh date / time / doctor / service. Mirrors
     * Admin\VisitController::restore so the front desk can do this
     * without admin escalation.
     */
    public function restore(Request $request, Visit $visit): RedirectResponse
    {
        if ($visit->status !== 'cancelled') {
            return back()->with('error', $this->msg(
                'Only cancelled visits can be restored.',
                'يمكن استعادة الزيارات الملغاة فقط.',
            ));
        }

        $data = $request->validate([
            'visit_date'     => ['required', 'date'],
            'scheduled_time' => ['nullable', 'date_format:H:i'],
            'doctor_id'      => ['nullable', 'integer', 'exists:doctors,id'],
            'service_id'     => ['nullable', 'integer', 'exists:services,id'],
        ]);

        $changes = ['status' => ['from' => 'cancelled', 'to' => 'waiting']];
        foreach ($data as $key => $newValue) {
            $oldValue = $visit->{$key};
            if ($key === 'visit_date' && $oldValue instanceof \DateTimeInterface) {
                $oldValue = $oldValue->format('Y-m-d');
            }
            if ((string) $oldValue !== (string) $newValue) {
                $changes[$key] = ['from' => $oldValue, 'to' => $newValue];
            }
        }

        // Hydrate FK display names for the patient email
        if (isset($changes['doctor_id'])) {
            $ids = array_filter([$changes['doctor_id']['from'] ?? null, $changes['doctor_id']['to'] ?? null]);
            $doctors = \App\Models\Doctor::whereIn('id', $ids)->pluck('name_ar', 'id');
            $changes['doctor_id']['from_name'] = $changes['doctor_id']['from'] ? ($doctors[$changes['doctor_id']['from']] ?? '—') : '—';
            $changes['doctor_id']['to_name']   = $changes['doctor_id']['to']   ? ($doctors[$changes['doctor_id']['to']]   ?? '—') : '—';
        }
        if (isset($changes['service_id'])) {
            $ids = array_filter([$changes['service_id']['from'] ?? null, $changes['service_id']['to'] ?? null]);
            $services = \App\Models\Service::whereIn('id', $ids)->pluck('name_ar', 'id');
            $changes['service_id']['from_name'] = $changes['service_id']['from'] ? ($services[$changes['service_id']['from']] ?? '—') : '—';
            $changes['service_id']['to_name']   = $changes['service_id']['to']   ? ($services[$changes['service_id']['to']]   ?? '—') : '—';
        }

        $visit->update(array_merge($data, ['status' => 'waiting']));

        AuditLogger::log('visit_restored', $visit, $changes);

        // Notify patient — visit is back on the calendar.
        $fresh = $visit->fresh(['patient', 'doctor', 'service']);
        $patient = $fresh->patient;
        if ($patient && $patient->email && $patient->wantsNotification('bookings', 'email')) {
            try {
                \Illuminate\Support\Facades\Notification::route('mail', $patient->email)
                    ->notify(new \App\Notifications\VisitRescheduledEmail(
                        $fresh,
                        $changes,
                        $fresh->doctor?->name_ar ?? $fresh->doctor?->name_en,
                        $fresh->service?->name_ar ?? $fresh->service?->name_en,
                    ));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[visit.restored.email] failed', [
                    'visit_id' => $fresh->id,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        return redirect()->back()->with('success', $this->msg(
            'Visit restored and rescheduled.',
            'تم استعادة الزيارة وإعادة جدولتها.',
        ));
    }
}
