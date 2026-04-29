<?php

namespace App\Http\Controllers\Patient;

use App\Models\Booking;
use App\Models\Invoice;
use App\Models\LoyaltyPoint;
use App\Models\PatientSatisfaction;
use App\Models\Prescription;
use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Unified patient activity feed — aggregates "things that happened on
 * my account" from across the existing tables (bookings, visits,
 * invoices, loyalty ledger, prescriptions, reviews) into a single
 * chronological timeline. No schema changes — pure read-side
 * aggregation.
 */
class PatientActivityController extends BasePatientController
{
    /** Hard cap on events fetched from each source. Keeps payload bounded
     *  for long-tenure patients (the timeline only shows the latest 50
     *  combined events anyway). */
    private const PER_SOURCE_LIMIT = 30;

    public function index(Request $request): Response
    {
        $patient = $this->patient($request);
        $pid = $patient->id;

        $events = collect();

        // ─── Bookings (created / confirmed / cancelled) ────────
        Booking::where('patient_id', $pid)
            ->latest('created_at')
            ->limit(self::PER_SOURCE_LIMIT)
            ->get(['id', 'booking_number', 'status', 'preferred_date', 'preferred_time', 'created_at', 'updated_at'])
            ->each(function ($b) use ($events) {
                $events->push([
                    'id'          => 'booking-'.$b->id,
                    'type'        => 'booking',
                    'subtype'     => $b->status, // unconfirmed/confirmed/cancelled
                    'occurred_at' => $b->updated_at?->toDateTimeString() ?? $b->created_at?->toDateTimeString(),
                    'title_ar'    => match ($b->status) {
                        'confirmed' => 'تم تأكيد حجزك',
                        'cancelled' => 'تم إلغاء حجز',
                        default     => 'حجز جديد',
                    },
                    'title_en'    => match ($b->status) {
                        'confirmed' => 'Booking confirmed',
                        'cancelled' => 'Booking cancelled',
                        default     => 'New booking',
                    },
                    'detail'      => "{$b->booking_number} · {$b->preferred_date} {$b->preferred_time}",
                    'href'        => '/bookings',
                ]);
            });

        // ─── Visits completed ─────────────────────────────────
        Visit::where('patient_id', $pid)
            ->where('status', 'completed')
            ->with(['doctor:id,name_ar,name_en', 'service:id,name_ar,name_en'])
            ->latest('visit_date')
            ->limit(self::PER_SOURCE_LIMIT)
            ->get()
            ->each(function ($v) use ($events) {
                $events->push([
                    'id'          => 'visit-'.$v->id,
                    'type'        => 'visit',
                    'subtype'     => 'completed',
                    'occurred_at' => ($v->visit_date instanceof \DateTimeInterface ? $v->visit_date->format('Y-m-d') : (string) $v->visit_date) . ' 12:00:00',
                    'title_ar'    => 'زيارة مكتملة',
                    'title_en'    => 'Visit completed',
                    'detail'      => trim(($v->doctor?->name_ar ?? $v->doctor?->name_en ?? '') . ($v->service ? ' · ' . ($v->service->name_ar ?? $v->service->name_en) : '')),
                    'href'        => '/visits/'.$v->id,
                ]);
            });

        // ─── Invoices ─────────────────────────────────────────
        Invoice::where('patient_id', $pid)
            ->latest('invoice_date')
            ->limit(self::PER_SOURCE_LIMIT)
            ->get(['id', 'invoice_number', 'status', 'total', 'paid_amount', 'invoice_date', 'updated_at'])
            ->each(function ($inv) use ($events) {
                $isPaid = $inv->status === 'paid' || ((float) $inv->paid_amount >= (float) $inv->total);
                $events->push([
                    'id'          => 'invoice-'.$inv->id,
                    'type'        => 'invoice',
                    'subtype'     => $isPaid ? 'paid' : 'unpaid',
                    'occurred_at' => $inv->updated_at?->toDateTimeString() ?? $inv->invoice_date,
                    'title_ar'    => $isPaid ? 'تم سداد فاتورة' : 'فاتورة جديدة',
                    'title_en'    => $isPaid ? 'Invoice paid'   : 'New invoice',
                    'detail'      => $inv->invoice_number . ' · ' . number_format((float) $inv->total, 0),
                    'href'        => '/invoices/'.$inv->id,
                ]);
            });

        // ─── Loyalty earnings + redemptions ───────────────────
        LoyaltyPoint::where('patient_id', $pid)
            ->whereIn('type', [LoyaltyPoint::TYPE_EARN, LoyaltyPoint::TYPE_REDEEM])
            ->latest('created_at')
            ->limit(self::PER_SOURCE_LIMIT)
            ->get(['id', 'points', 'type', 'description', 'created_at'])
            ->each(function ($p) use ($events) {
                $earn = $p->type === LoyaltyPoint::TYPE_EARN;
                $events->push([
                    'id'          => 'loyalty-'.$p->id,
                    'type'        => 'loyalty',
                    'subtype'     => $p->type,
                    'occurred_at' => $p->created_at?->toDateTimeString(),
                    'title_ar'    => $earn ? 'نقاط ولاء جديدة' : 'استبدال نقاط',
                    'title_en'    => $earn ? 'Loyalty points earned' : 'Loyalty points redeemed',
                    'detail'      => ($earn ? '+' : '') . $p->points . ' pts'
                                     . ($p->description ? ' · ' . \Illuminate\Support\Str::limit($p->description, 60) : ''),
                    'href'        => '/loyalty',
                ]);
            });

        // ─── Prescriptions ────────────────────────────────────
        Prescription::where('patient_id', $pid)
            ->with('doctor:id,name_ar,name_en')
            ->latest('created_at')
            ->limit(self::PER_SOURCE_LIMIT)
            ->get(['id', 'doctor_id', 'visit_id', 'created_at'])
            ->each(function ($rx) use ($events) {
                $events->push([
                    'id'          => 'prescription-'.$rx->id,
                    'type'        => 'prescription',
                    'subtype'     => 'created',
                    'occurred_at' => $rx->created_at?->toDateTimeString(),
                    'title_ar'    => 'وصفة طبية جديدة',
                    'title_en'    => 'New prescription',
                    'detail'      => $rx->doctor?->name_ar ?? $rx->doctor?->name_en ?? '',
                    'href'        => '/prescriptions/'.$rx->id,
                ]);
            });

        // ─── Reviews submitted ────────────────────────────────
        PatientSatisfaction::where('patient_id', $pid)
            ->whereNotNull('overall_rating')
            ->latest('created_at')
            ->limit(self::PER_SOURCE_LIMIT)
            ->get(['id', 'overall_rating', 'visit_id', 'created_at'])
            ->each(function ($r) use ($events) {
                $events->push([
                    'id'          => 'review-'.$r->id,
                    'type'        => 'review',
                    'subtype'     => 'submitted',
                    'occurred_at' => $r->created_at?->toDateTimeString(),
                    'title_ar'    => 'تقييم مُقدَّم',
                    'title_en'    => 'Review submitted',
                    'detail'      => str_repeat('★', $r->overall_rating) . str_repeat('☆', 5 - $r->overall_rating),
                    'href'        => '/feedback',
                ]);
            });

        // Sort all events by occurred_at desc, take top 50.
        $timeline = $events
            ->sortByDesc('occurred_at')
            ->values()
            ->take(50);

        return Inertia::render('Patient/Activity/Index', [
            'events' => $timeline,
        ]);
    }
}
