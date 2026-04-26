<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Visit;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrashController extends Controller
{
    /**
     * Map of allowed model types to their Eloquent classes.
     */
    private const MODEL_MAP = [
        'patients' => Patient::class,
        'bookings' => Booking::class,
        'visits' => Visit::class,
        'invoices' => Invoice::class,
        'payments' => Payment::class,
        'prescriptions' => Prescription::class,
        'doctors' => Doctor::class,
    ];

    /**
     * Display the trash (recycle bin) page with all soft-deleted records.
     */
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'type' => 'nullable|string|in:all,patients,bookings,visits,invoices,payments,prescriptions,doctors',
            'search' => 'nullable|string|max:100',
        ]);

        $activeType = $filters['type'] ?? 'all';
        $search = $filters['search'] ?? null;

        $trashedData = [];
        $counts = [];

        foreach (self::MODEL_MAP as $key => $modelClass) {
            $count = $modelClass::onlyTrashed()->count();
            $counts[$key] = $count;

            // Only load data for the active tab (or all if 'all')
            if ($activeType === 'all' || $activeType === $key) {
                $query = $modelClass::onlyTrashed();

                if ($search) {
                    $query = $this->applySearch($query, $key, $search);
                }

                $trashedData[$key] = $query
                    ->latest('deleted_at')
                    ->limit($activeType === 'all' ? 5 : 50)
                    ->get()
                    ->map(fn ($item) => $this->formatTrashedItem($item, $key));
            }
        }

        return Inertia::render('Admin/Trash/Index', [
            'trashedData' => $trashedData,
            'counts' => $counts,
            'totalCount' => array_sum($counts),
            'filters' => [
                'type' => $activeType,
                'search' => $search,
            ],
        ]);
    }

    /**
     * Restore a soft-deleted record.
     */
    public function restore(Request $request, string $type, int $id): RedirectResponse
    {
        $modelClass = self::MODEL_MAP[$type] ?? null;

        if (!$modelClass) {
            return back()->with('error', 'Invalid resource type.');
        }

        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $item->restore();

        AuditLogger::log('restored', $item);

        // If restoring a patient, also restore the linked user
        if ($type === 'patients' && $item->user_id) {
            $trashedUser = \App\Models\User::onlyTrashed()->find($item->user_id);
            $trashedUser?->restore();
        }

        $label = $this->getItemLabel($item, $type);

        return back()->with('success', "تم استعادة {$label} بنجاح / {$label} restored successfully.");
    }

    /**
     * Permanently delete a soft-deleted record (force delete).
     */
    public function forceDelete(Request $request, string $type, int $id): RedirectResponse
    {
        $modelClass = self::MODEL_MAP[$type] ?? null;

        if (!$modelClass) {
            return back()->with('error', 'Invalid resource type.');
        }

        $item = $modelClass::onlyTrashed()->findOrFail($id);
        $label = $this->getItemLabel($item, $type);

        AuditLogger::log('force_deleted', $item);

        $item->forceDelete();

        return back()->with('success', "تم حذف {$label} نهائياً / {$label} permanently deleted.");
    }

    /**
     * Restore all trashed items of a given type.
     */
    public function restoreAll(Request $request, string $type): RedirectResponse
    {
        $modelClass = self::MODEL_MAP[$type] ?? null;

        if (!$modelClass) {
            return back()->with('error', 'Invalid resource type.');
        }

        $count = $modelClass::onlyTrashed()->count();
        $modelClass::onlyTrashed()->each(function ($item) use ($type) {
            $item->restore();

            // Restore linked users for patients
            if ($type === 'patients' && $item->user_id) {
                \App\Models\User::onlyTrashed()->where('id', $item->user_id)->first()?->restore();
            }
        });

        $typeLabels = [
            'patients' => 'المرضى / Patients',
            'bookings' => 'الحجوزات / Bookings',
            'visits' => 'الزيارات / Visits',
            'invoices' => 'الفواتير / Invoices',
            'payments' => 'المدفوعات / Payments',
            'prescriptions' => 'الوصفات / Prescriptions',
            'doctors' => 'الأطباء / Doctors',
        ];

        return back()->with('success', "تم استعادة {$count} " . ($typeLabels[$type] ?? $type) . " بنجاح.");
    }

    /**
     * Empty trash for a specific type (force delete all).
     */
    public function emptyTrash(Request $request, string $type): RedirectResponse
    {
        $modelClass = self::MODEL_MAP[$type] ?? null;

        if (!$modelClass) {
            return back()->with('error', 'Invalid resource type.');
        }

        $count = $modelClass::onlyTrashed()->count();
        $modelClass::onlyTrashed()->forceDelete();

        return back()->with('success', "تم حذف {$count} عنصر نهائياً / {$count} items permanently deleted.");
    }

    /**
     * Restore a list of specific trashed items in one shot.
     * Body: { type: 'patients', ids: [1, 2, 3] }
     */
    public function bulkRestore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(self::MODEL_MAP)),
            'ids'  => 'required|array|min:1|max:200',
            'ids.*' => 'integer',
        ]);

        $modelClass = self::MODEL_MAP[$data['type']];
        $items = $modelClass::onlyTrashed()->whereIn('id', $data['ids'])->get();

        $count = 0;
        foreach ($items as $item) {
            $item->restore();
            // For patients, also restore the linked user
            if ($data['type'] === 'patients' && $item->user_id) {
                \App\Models\User::onlyTrashed()->where('id', $item->user_id)->first()?->restore();
            }
            $count++;
        }

        AuditLogger::log('bulk_restored', null, "Restored {$count} {$data['type']} items.");

        return back()->with('success', "تم استعادة {$count} عنصر / Restored {$count} items.");
    }

    /**
     * Force-delete a list of specific trashed items.
     * Body: { type: 'patients', ids: [1, 2, 3] }
     */
    public function bulkForceDelete(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => 'required|string|in:' . implode(',', array_keys(self::MODEL_MAP)),
            'ids'  => 'required|array|min:1|max:200',
            'ids.*' => 'integer',
        ]);

        $modelClass = self::MODEL_MAP[$data['type']];
        $count = $modelClass::onlyTrashed()->whereIn('id', $data['ids'])->forceDelete();

        AuditLogger::log('bulk_force_deleted', null, "Permanently deleted {$count} {$data['type']} items.");

        return back()->with('success', "تم حذف {$count} عنصر نهائياً / Permanently deleted {$count} items.");
    }

    /**
     * Apply search filter based on model type.
     */
    private function applySearch($query, string $type, string $search)
    {
        return match ($type) {
            'patients' => $query->where(function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('file_number', 'like', "%{$search}%");
            }),
            'doctors' => $query->where(function ($q) use ($search) {
                $q->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%");
            }),
            'bookings' => $query->where(function ($q) use ($search) {
                $q->where('booking_number', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }),
            'invoices' => $query->where('invoice_number', 'like', "%{$search}%"),
            'payments' => $query->where('reference_number', 'like', "%{$search}%"),
            'prescriptions' => $query->whereHas('patient', fn ($q) => $q->withTrashed()->where('full_name', 'like', "%{$search}%")),
            'visits' => $query->whereHas('patient', fn ($q) => $q->withTrashed()->where('full_name', 'like', "%{$search}%")),
            default => $query,
        };
    }

    /**
     * Format a trashed item for the frontend display.
     */
    private function formatTrashedItem($item, string $type): array
    {
        $base = [
            'id' => $item->id,
            'type' => $type,
            'deleted_at' => $item->deleted_at->toISOString(),
            'deleted_ago' => $item->deleted_at->diffForHumans(),
        ];

        return match ($type) {
            'patients' => array_merge($base, [
                'title' => $item->full_name,
                'subtitle' => $item->file_number . ' - ' . $item->phone,
                'icon' => 'user',
            ]),
            'doctors' => array_merge($base, [
                'title' => $item->name_en . ' / ' . $item->name_ar,
                'subtitle' => $item->specialization_en ?? $item->module ?? '',
                'icon' => 'stethoscope',
            ]),
            'bookings' => array_merge($base, [
                'title' => ($item->booking_number ?? 'Booking #' . $item->id),
                'subtitle' => ($item->full_name ?? '') . ' - ' . ($item->preferred_date ?? $item->booking_date ?? ''),
                'icon' => 'calendar',
            ]),
            'visits' => array_merge($base, [
                'title' => 'Visit #' . $item->id,
                'subtitle' => ($item->visit_date ?? '') . ' - ' . $item->status,
                'icon' => 'clipboard',
            ]),
            'invoices' => array_merge($base, [
                'title' => $item->invoice_number ?? ('Invoice #' . $item->id),
                'subtitle' => number_format($item->total ?? 0, 2) . ' - ' . ($item->status ?? 'unknown'),
                'icon' => 'file-text',
            ]),
            'payments' => array_merge($base, [
                'title' => 'Payment #' . $item->id,
                'subtitle' => number_format($item->amount ?? 0, 2) . ' - ' . ($item->payment_date ?? ''),
                'icon' => 'credit-card',
            ]),
            'prescriptions' => array_merge($base, [
                'title' => 'Prescription #' . $item->id,
                'subtitle' => ($item->created_at?->toDateString() ?? ''),
                'icon' => 'pill',
            ]),
            default => array_merge($base, [
                'title' => ucfirst($type) . ' #' . $item->id,
                'subtitle' => '',
                'icon' => 'trash',
            ]),
        };
    }

    /**
     * Get a human-readable label for an item.
     */
    private function getItemLabel($item, string $type): string
    {
        return match ($type) {
            'patients' => $item->full_name ?? ('Patient #' . $item->id),
            'doctors' => $item->name_en ?? ('Doctor #' . $item->id),
            'bookings' => $item->booking_number ?? ('Booking #' . $item->id),
            'invoices' => $item->invoice_number ?? ('Invoice #' . $item->id),
            default => ucfirst(rtrim($type, 's')) . ' #' . $item->id,
        };
    }
}
