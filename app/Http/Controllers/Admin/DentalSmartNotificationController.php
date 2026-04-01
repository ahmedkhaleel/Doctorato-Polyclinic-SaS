<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DentalSmartNotification;
use App\Models\Patient;
use App\Services\AuditLogger;
use App\Services\DentalSmartNotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DentalSmartNotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = DentalSmartNotification::with([
            'patient:id,full_name,file_number,phone',
            'doctor:id,name_ar,name_en',
        ]);

        // Filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('file_number', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                })
                ->orWhere('message_ar', 'like', "%{$search}%")
                ->orWhere('message_en', 'like', "%{$search}%");
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $notifications = $query->latest()
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Dental/SmartNotifications/Index', [
            'notifications' => $notifications,
            'filters' => $request->only(['type', 'status', 'search', 'date_from', 'date_to']),
            'stats' => DentalSmartNotificationService::getStats(),
            'types' => DentalSmartNotification::TYPE_LABELS,
        ]);
    }

    /**
     * Send a manual notification to a patient.
     */
    public function sendManual(Request $request)
    {
        $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'type' => 'required|string|in:' . implode(',', DentalSmartNotification::TYPES),
            'message_ar' => 'required|string|max:500',
            'message_en' => 'nullable|string|max:500',
        ]);

        $patient = Patient::findOrFail($request->patient_id);

        $notification = DentalSmartNotificationService::sendManual(
            patient: $patient,
            type: $request->type,
            messageAr: $request->message_ar,
            messageEn: $request->message_en,
        );

        // Try to send immediately
        DentalSmartNotificationService::sendNotification($notification);

        AuditLogger::log('created', $notification, null, "Manual smart notification sent to {$patient->full_name}");

        return redirect()->back()->with('success', 'تم إرسال التنبيه بنجاح');
    }

    /**
     * Resend a failed notification.
     */
    public function resend(DentalSmartNotification $notification)
    {
        $notification->update([
            'status' => 'pending',
            'scheduled_at' => now(),
            'failure_reason' => null,
        ]);

        DentalSmartNotificationService::sendNotification($notification);

        return redirect()->back()->with('success', 'تمت إعادة إرسال التنبيه');
    }

    /**
     * Cancel a pending notification.
     */
    public function cancel(DentalSmartNotification $notification)
    {
        if ($notification->status !== 'pending') {
            return redirect()->back()->with('error', 'لا يمكن إلغاء تنبيه تم إرساله بالفعل');
        }

        $notification->markCancelled();
        AuditLogger::log('updated', $notification, null, 'Smart notification cancelled');

        return redirect()->back()->with('success', 'تم إلغاء التنبيه');
    }

    /**
     * Mark that the patient responded/acted on a notification.
     */
    public function markResponded(DentalSmartNotification $notification)
    {
        $notification->markPatientResponded();

        return redirect()->back()->with('success', 'تم تسجيل استجابة المريض');
    }

    /**
     * Trigger a manual scan for all notification types.
     */
    public function triggerScan()
    {
        $results = DentalSmartNotificationService::scanAndSend();

        $message = "تم الفحص: {$results['followup_scanned']} تذكير متابعة، {$results['stalled_scanned']} خطة متوقفة، {$results['sent']} أُرسل، {$results['failed']} فشل";

        return redirect()->back()->with('success', $message);
    }

    /**
     * Delete a notification record.
     */
    public function destroy(DentalSmartNotification $notification)
    {
        AuditLogger::log('deleted', $notification);
        $notification->delete();

        return redirect()->back()->with('success', 'تم حذف التنبيه');
    }
}
