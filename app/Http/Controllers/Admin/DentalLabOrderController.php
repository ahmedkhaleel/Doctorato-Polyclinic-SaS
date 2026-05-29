<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dental\StoreDentalLabOrderRequest;
use App\Http\Requests\Dental\UpdateDentalLabOrderRequest;
use App\Models\DentalLabOrder;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\DentalLabOrderReadyNotification;
use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\DentalInvoiceService;
use App\Services\SmsNotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DentalLabOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = DentalLabOrder::with([
            'patient:id,full_name,file_number,phone',
            'doctor:id,name_ar,name_en',
        ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('patient', function ($pq) use ($search) {
                    $pq->where('full_name', 'like', "%{$search}%")
                        ->orWhere('file_number', 'like', "%{$search}%");
                })
                ->orWhere('lab_name', 'like', "%{$search}%")
                ->orWhere('tooth_number', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        if ($request->boolean('overdue')) {
            $query->overdue();
        }
        if ($request->filled('date_from')) {
            $query->whereDate('order_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('order_date', '<=', $request->date_to);
        }

        $orders = $query->latest('order_date')
            ->paginate(20)
            ->withQueryString();

        // Pipeline counts for visual status stepper
        $pipelineCounts = DentalLabOrder::selectRaw('status, COUNT(*) as cnt')
            ->whereNotIn('status', ['cancelled'])
            ->groupBy('status')
            ->pluck('cnt', 'status')
            ->toArray();

        $stats = [
            'total_pending' => DentalLabOrder::pending()->count(),
            'overdue' => DentalLabOrder::overdue()->count(),
            'this_month_cost' => DentalLabOrder::where('order_date', '>=', now()->startOfMonth())->sum('cost'),
            'count_ordered' => $pipelineCounts['ordered'] ?? 0,
            'count_in_production' => $pipelineCounts['in_production'] ?? 0,
            'count_ready' => $pipelineCounts['ready'] ?? 0,
            'count_delivered' => $pipelineCounts['delivered'] ?? 0,
            'count_completed' => $pipelineCounts['completed'] ?? 0,
        ];

        return Inertia::render('Admin/Dental/LabOrders/Index', [
            'orders' => $orders,
            'filters' => $request->only(['status', 'search', 'overdue', 'date_from', 'date_to']),
            'stats' => $stats,
            'itemTypes' => DentalLabOrder::ITEM_TYPES,
            'materials' => DentalLabOrder::MATERIALS,
        ]);
    }

    public function create(Request $request)
    {
        $patient = null;
        if ($request->filled('patient_id')) {
            $patient = Patient::find($request->patient_id);
        }

        return Inertia::render('Admin/Dental/LabOrders/Create', [
            'patient' => $patient,
            'patients' => Patient::active()->select('id', 'full_name', 'file_number', 'phone')->limit(100)->get(),
            'doctors' => Doctor::dental()->active()->select('id', 'name_ar', 'name_en')->get(),
            'itemTypes' => DentalLabOrder::ITEM_TYPES,
            'materials' => DentalLabOrder::MATERIALS,
        ]);
    }

    public function store(StoreDentalLabOrderRequest $request)
    {
        $data = $request->validated();

        $data['status'] = 'ordered';

        $order = DentalLabOrder::create($data);

        AuditLogger::log('created', $order);

        return redirect()->route('admin.dental.lab-orders.index')
            ->with('success', 'تم إنشاء طلب المعمل بنجاح');
    }

    public function update(UpdateDentalLabOrderRequest $request, DentalLabOrder $labOrder)
    {
        $data = $request->validated();

        if (isset($data['status']) && $data['status'] === 'delivered' && !$labOrder->delivered_date) {
            $data['delivered_date'] = now();
        }

        $wasDelivered = in_array($labOrder->status, ['delivered', 'completed']);

        $oldStatus = $labOrder->status;
        $labOrder->update($data);

        AuditLogger::log('updated', $labOrder);

        // Auto-add lab charge to invoice when delivered
        if (!$wasDelivered && in_array($labOrder->status, ['delivered', 'completed'])) {
            app(DentalInvoiceService::class)->addLabOrderToInvoice($labOrder);
        }

        // Notify doctor + secretaries on key status changes
        if (isset($data['status']) && $data['status'] !== $oldStatus && in_array($data['status'], ['ready', 'delivered', 'adjustment'])) {
            $recipients = User::where('is_active', true)
                ->where(function ($q) use ($labOrder) {
                    $q->whereHas('role', fn ($r) => $r->where('name', 'secretary'))
                      ->orWhereHas('doctor', fn ($d) => $d->where('id', $labOrder->doctor_id));
                })->get();
            \App\Jobs\SendNotificationJob::dispatch($recipients, new DentalLabOrderReadyNotification($labOrder, $data['status']), 'dental_lab_order_status');
        }

        // SMS: Notify patient when lab order is ready for pickup/fitting
        if (isset($data['status']) && $data['status'] === 'ready' && $oldStatus !== 'ready'
            && Setting::get('sms_on_lab_order_ready', '0') === '1') {
            try {
                $patient = $labOrder->patient;
                if ($patient) {
                    SmsNotificationService::dentalLabOrderReady($patient, $labOrder->item_type ?? '');
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('SMS lab order ready failed', ['lab_order_id' => $labOrder->id, 'error' => $e->getMessage()]);
            }
        }

        // Smart Notification: Queue lab order ready notification for patient
        if (isset($data['status']) && in_array($data['status'], ['ready', 'delivered']) && $oldStatus !== $data['status']) {
            try {
                \App\Services\DentalSmartNotificationService::queueLabOrderReady($labOrder->fresh(['patient']));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Smart notification lab order failed', ['id' => $labOrder->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->back()->with('success', 'تم تحديث طلب المعمل بنجاح');
    }

    public function destroy(DentalLabOrder $labOrder)
    {
        AuditLogger::log('deleted', $labOrder);
        // Void its invoice line first so deletion leaves no phantom charge.
        app(DentalInvoiceService::class)->reverseForLabOrder($labOrder);
        $labOrder->delete();

        return redirect()->route('admin.dental.lab-orders.index')
            ->with('success', 'تم حذف طلب المعمل بنجاح');
    }

    /**
     * Lab Dashboard — dedicated lab order management overview.
     */
    public function dashboard()
    {
        $now = now();
        $thisMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // ── Pipeline: orders by status ──
        $pipeline = DentalLabOrder::selectRaw("status, COUNT(*) as count")
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statuses = ['ordered', 'in_production', 'ready', 'delivered', 'adjustment', 'completed'];
        $pipelineFull = [];
        foreach ($statuses as $s) {
            $pipelineFull[$s] = $pipeline[$s] ?? 0;
        }

        // ── KPI Stats ──
        $totalActive = DentalLabOrder::pending()->count();
        $overdueCount = DentalLabOrder::overdue()->count();
        $thisMonthOrdered = DentalLabOrder::where('order_date', '>=', $thisMonth)->count();
        $thisMonthDelivered = DentalLabOrder::whereIn('status', ['delivered', 'completed'])
            ->where('delivered_date', '>=', $thisMonth)->count();
        $lastMonthDelivered = DentalLabOrder::whereIn('status', ['delivered', 'completed'])
            ->whereBetween('delivered_date', [$lastMonth, $lastMonthEnd])->count();

        // Cost & profit this month
        $thisMonthCost = DentalLabOrder::where('order_date', '>=', $thisMonth)->sum('cost');
        $thisMonthCharge = DentalLabOrder::where('order_date', '>=', $thisMonth)->sum('patient_charge');
        $lastMonthCost = DentalLabOrder::whereBetween('order_date', [$lastMonth, $lastMonthEnd])->sum('cost');

        // Avg delivery days (delivered this month)
        $avgDeliveryDays = DentalLabOrder::whereIn('status', ['delivered', 'completed'])
            ->whereNotNull('delivered_date')
            ->whereNotNull('order_date')
            ->where('delivered_date', '>=', $thisMonth)
            ->selectRaw('AVG(DATEDIFF(delivered_date, order_date)) as avg_days')
            ->value('avg_days');

        // ── Overdue orders list ──
        $overdueOrders = DentalLabOrder::overdue()
            ->with(['patient:id,full_name,file_number,phone', 'doctor:id,name_ar,name_en'])
            ->orderBy('expected_date')
            ->limit(15)
            ->get();

        // ── Upcoming deliveries (expected in next 7 days) ──
        $upcomingDeliveries = DentalLabOrder::pending()
            ->whereBetween('expected_date', [$now->toDateString(), $now->copy()->addDays(7)->toDateString()])
            ->with(['patient:id,full_name,file_number', 'doctor:id,name_ar,name_en'])
            ->orderBy('expected_date')
            ->limit(15)
            ->get();

        // ── Lab performance comparison ──
        $labPerformance = DentalLabOrder::selectRaw("
                lab_name,
                COUNT(*) as total_orders,
                SUM(CASE WHEN status IN ('delivered','completed') THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN status NOT IN ('delivered','completed') AND expected_date < NOW() THEN 1 ELSE 0 END) as overdue_count,
                SUM(cost) as total_cost,
                SUM(patient_charge) as total_charge,
                AVG(CASE WHEN status IN ('delivered','completed') AND delivered_date IS NOT NULL AND order_date IS NOT NULL
                    THEN DATEDIFF(delivered_date, order_date) END) as avg_days
            ")
            ->whereNotNull('lab_name')
            ->where('lab_name', '!=', '')
            ->groupBy('lab_name')
            ->orderByDesc('total_orders')
            ->limit(10)
            ->get();

        // ── Item type breakdown (this month) ──
        $itemTypeBreakdown = DentalLabOrder::selectRaw("item_type, COUNT(*) as count, SUM(cost) as total_cost, SUM(patient_charge) as total_charge")
            ->where('order_date', '>=', $thisMonth)
            ->groupBy('item_type')
            ->orderByDesc('count')
            ->get();

        // ── Monthly trend (last 6 months) ──
        $monthlyTrend = DentalLabOrder::selectRaw("
                DATE_FORMAT(order_date, '%Y-%m') as month,
                COUNT(*) as ordered,
                SUM(CASE WHEN status IN ('delivered','completed') THEN 1 ELSE 0 END) as delivered,
                SUM(cost) as cost,
                SUM(patient_charge) as charge
            ")
            ->where('order_date', '>=', $now->copy()->subMonths(6)->startOfMonth())
            ->groupByRaw("DATE_FORMAT(order_date, '%Y-%m')")
            ->orderBy('month')
            ->get();

        // ── Recent activity (last 20 status changes) ──
        $recentOrders = DentalLabOrder::with(['patient:id,full_name,file_number', 'doctor:id,name_ar,name_en'])
            ->latest('updated_at')
            ->limit(20)
            ->get();

        return Inertia::render('Admin/Dental/LabOrders/Dashboard', [
            'pipeline' => $pipelineFull,
            'stats' => [
                'total_active' => $totalActive,
                'overdue' => $overdueCount,
                'this_month_ordered' => $thisMonthOrdered,
                'this_month_delivered' => $thisMonthDelivered,
                'last_month_delivered' => $lastMonthDelivered,
                'this_month_cost' => (float) $thisMonthCost,
                'this_month_charge' => (float) $thisMonthCharge,
                'this_month_profit' => (float) ($thisMonthCharge - $thisMonthCost),
                'last_month_cost' => (float) $lastMonthCost,
                'avg_delivery_days' => round($avgDeliveryDays ?? 0, 1),
            ],
            'overdueOrders' => $overdueOrders,
            'upcomingDeliveries' => $upcomingDeliveries,
            'labPerformance' => $labPerformance,
            'itemTypeBreakdown' => $itemTypeBreakdown,
            'monthlyTrend' => $monthlyTrend,
            'recentOrders' => $recentOrders,
        ]);
    }

    /**
     * Lab Profitability Analysis — deep analytics & lab comparison.
     */
    public function profitability(Request $request)
    {
        $dateFrom = $request->input('date_from', now()->subMonths(6)->startOfMonth()->toDateString());
        $dateTo   = $request->input('date_to', now()->toDateString());

        // ── 1. Lab Comparison: cost per item type across labs ──
        $labComparison = DentalLabOrder::selectRaw("
                lab_name,
                item_type,
                COUNT(*) as order_count,
                AVG(cost) as avg_cost,
                MIN(cost) as min_cost,
                MAX(cost) as max_cost,
                SUM(cost) as total_cost,
                AVG(patient_charge) as avg_charge,
                SUM(patient_charge) as total_charge,
                SUM(patient_charge - cost) as total_profit,
                AVG(CASE WHEN status IN ('delivered','completed') AND delivered_date IS NOT NULL AND order_date IS NOT NULL
                    THEN DATEDIFF(delivered_date, order_date) END) as avg_delivery_days,
                SUM(CASE WHEN status IN ('delivered','completed') THEN 1 ELSE 0 END) as delivered_count,
                SUM(CASE WHEN status = 'adjustment' THEN 1 ELSE 0 END) as adjustment_count
            ")
            ->whereNotNull('lab_name')
            ->where('lab_name', '!=', '')
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->groupBy('lab_name', 'item_type')
            ->orderBy('lab_name')
            ->orderBy('item_type')
            ->get();

        // ── 2. Lab Quality Ranking (adjustment/remake rate) ──
        $labQuality = DentalLabOrder::selectRaw("
                lab_name,
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'adjustment' THEN 1 ELSE 0 END) as adjustment_count,
                SUM(CASE WHEN status IN ('delivered','completed') THEN 1 ELSE 0 END) as delivered_count,
                ROUND(SUM(CASE WHEN status = 'adjustment' THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as adjustment_rate,
                SUM(cost) as total_cost,
                SUM(patient_charge) as total_charge,
                AVG(cost) as avg_cost,
                AVG(CASE WHEN status IN ('delivered','completed') AND delivered_date IS NOT NULL AND order_date IS NOT NULL
                    THEN DATEDIFF(delivered_date, order_date) END) as avg_delivery_days,
                SUM(CASE WHEN expected_date IS NOT NULL AND expected_date < delivered_date THEN 1 ELSE 0 END) as late_deliveries,
                SUM(CASE WHEN expected_date IS NOT NULL AND delivered_date IS NOT NULL AND delivered_date <= expected_date THEN 1 ELSE 0 END) as on_time_deliveries
            ")
            ->whereNotNull('lab_name')
            ->where('lab_name', '!=', '')
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->groupBy('lab_name')
            ->orderByDesc('total_orders')
            ->get()
            ->map(function ($lab) {
                $onTimeTotal = $lab->on_time_deliveries + $lab->late_deliveries;
                $lab->on_time_rate = $onTimeTotal > 0
                    ? round($lab->on_time_deliveries / $onTimeTotal * 100, 1)
                    : null;
                $lab->profit_margin = $lab->total_charge > 0
                    ? round(($lab->total_charge - $lab->total_cost) / $lab->total_charge * 100, 1)
                    : 0;
                // Quality score: 40% on-time, 30% low-adjustment, 30% cost-efficiency
                $onTimeScore = min(100, ($lab->on_time_rate ?? 50));
                $adjustmentScore = max(0, 100 - ($lab->adjustment_rate * 5));
                $lab->quality_score = round($onTimeScore * 0.4 + $adjustmentScore * 0.3 + min(100, $lab->profit_margin) * 0.3);
                return $lab;
            })
            ->sortByDesc('quality_score')
            ->values();

        // ── 3. Best Lab Recommendation per item type ──
        $bestLabPerType = DentalLabOrder::selectRaw("
                item_type,
                lab_name,
                COUNT(*) as order_count,
                AVG(cost) as avg_cost,
                AVG(CASE WHEN status IN ('delivered','completed') AND delivered_date IS NOT NULL AND order_date IS NOT NULL
                    THEN DATEDIFF(delivered_date, order_date) END) as avg_days,
                ROUND(SUM(CASE WHEN status = 'adjustment' THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as adj_rate,
                ROUND(SUM(CASE WHEN status IN ('delivered','completed') THEN 1 ELSE 0 END) / COUNT(*) * 100, 1) as delivery_rate
            ")
            ->whereNotNull('lab_name')
            ->where('lab_name', '!=', '')
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->groupBy('item_type', 'lab_name')
            ->havingRaw('COUNT(*) >= 3')
            ->orderBy('item_type')
            ->get()
            ->groupBy('item_type')
            ->map(function ($labs, $type) {
                // Score each lab: low cost + fast delivery + low adjustments
                return $labs->map(function ($lab) {
                    $costScore = max(0, 100 - (($lab->avg_cost ?? 0) / 100));
                    $speedScore = max(0, 100 - (($lab->avg_days ?? 10) * 3));
                    $qualityScore = max(0, 100 - (($lab->adj_rate ?? 0) * 5));
                    $lab->recommendation_score = round($qualityScore * 0.4 + $speedScore * 0.3 + $costScore * 0.3);
                    return $lab;
                })->sortByDesc('recommendation_score')->values();
            });

        // ── 4. Profitability by item type ──
        $profitByType = DentalLabOrder::selectRaw("
                item_type,
                COUNT(*) as order_count,
                SUM(cost) as total_cost,
                SUM(patient_charge) as total_charge,
                SUM(patient_charge - cost) as total_profit,
                AVG(cost) as avg_cost,
                AVG(patient_charge) as avg_charge,
                AVG(patient_charge - cost) as avg_profit,
                ROUND(SUM(patient_charge - cost) / NULLIF(SUM(patient_charge), 0) * 100, 1) as profit_margin
            ")
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->groupBy('item_type')
            ->orderByDesc('total_profit')
            ->get();

        // ── 5. Monthly profitability trend ──
        $monthlyProfit = DentalLabOrder::selectRaw("
                DATE_FORMAT(order_date, '%Y-%m') as month,
                SUM(cost) as total_cost,
                SUM(patient_charge) as total_charge,
                SUM(patient_charge - cost) as profit,
                COUNT(*) as order_count
            ")
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->groupByRaw("DATE_FORMAT(order_date, '%Y-%m')")
            ->orderBy('month')
            ->get();

        // ── 6. Summary KPIs ──
        $summary = DentalLabOrder::selectRaw("
                COUNT(*) as total_orders,
                SUM(cost) as total_cost,
                SUM(patient_charge) as total_charge,
                SUM(patient_charge - cost) as total_profit,
                AVG(patient_charge - cost) as avg_profit_per_order,
                ROUND(SUM(patient_charge - cost) / NULLIF(SUM(patient_charge), 0) * 100, 1) as overall_margin,
                COUNT(DISTINCT lab_name) as total_labs,
                AVG(CASE WHEN status IN ('delivered','completed') AND delivered_date IS NOT NULL
                    THEN DATEDIFF(delivered_date, order_date) END) as avg_delivery_days
            ")
            ->whereBetween('order_date', [$dateFrom, $dateTo])
            ->first();

        return Inertia::render('Admin/Dental/LabOrders/Profitability', [
            'labComparison'   => $labComparison,
            'labQuality'      => $labQuality,
            'bestLabPerType'  => $bestLabPerType,
            'profitByType'    => $profitByType,
            'monthlyProfit'   => $monthlyProfit,
            'summary'         => $summary,
            'filters'         => ['date_from' => $dateFrom, 'date_to' => $dateTo],
            'itemTypes'       => DentalLabOrder::ITEM_TYPES,
            'materials'       => DentalLabOrder::MATERIALS,
        ]);
    }

    /**
     * Bulk update status for multiple lab orders.
     */
    public function bulkUpdateStatus(Request $request)
    {
        $data = $request->validate([
            'order_ids'  => 'required|array|min:1',
            'order_ids.*' => 'exists:dental_lab_orders,id',
            'status'      => 'required|string|in:ordered,in_production,ready,delivered,adjustment,completed',
        ]);

        $orders = DentalLabOrder::whereIn('id', $data['order_ids'])
            ->with('patient:id,full_name,phone')
            ->get();

        $count = 0;
        $invoiceService = app(DentalInvoiceService::class);

        // Pre-load recipients once if status requires notification (avoids N+1 per order)
        $recipients = null;
        if (in_array($data['status'], ['ready', 'delivered', 'adjustment'])) {
            $doctorIds = $orders->pluck('doctor_id')->unique()->filter()->values()->toArray();
            $recipients = User::where('is_active', true)
                ->where(function ($q) use ($doctorIds) {
                    $q->whereHas('role', fn ($r) => $r->where('name', 'secretary'))
                      ->orWhereHas('doctor', fn ($d) => $d->whereIn('id', $doctorIds));
                })->get();
        }

        foreach ($orders as $order) {
            $oldStatus = $order->status;
            if ($oldStatus === $data['status']) continue;

            $updateData = ['status' => $data['status']];

            // Auto-set delivered_date
            if ($data['status'] === 'delivered' && !$order->delivered_date) {
                $updateData['delivered_date'] = now();
            }

            $wasDelivered = in_array($oldStatus, ['delivered', 'completed']);
            $order->update($updateData);
            $count++;

            AuditLogger::log('updated', $order);

            // Auto-add lab charge to invoice when delivered/completed
            if (!$wasDelivered && in_array($data['status'], ['delivered', 'completed'])) {
                $invoiceService->addLabOrderToInvoice($order);
            }

            // Notify doctor + secretaries on key status changes (recipients pre-loaded)
            if ($recipients && $recipients->isNotEmpty()) {
                \App\Jobs\SendNotificationJob::dispatch($recipients, new DentalLabOrderReadyNotification($order, $data['status']), 'dental_lab_order_bulk_status');
            }
        }

        return redirect()->back()->with('success', "تم تحديث {$count} طلب بنجاح");
    }

    /**
     * Bulk send SMS notifications to patients for ready orders.
     */
    public function bulkSmsNotify(Request $request)
    {
        $data = $request->validate([
            'order_ids'  => 'required|array|min:1',
            'order_ids.*' => 'exists:dental_lab_orders,id',
        ]);

        $orders = DentalLabOrder::whereIn('id', $data['order_ids'])
            ->with('patient:id,full_name,phone')
            ->get();

        $recipients = [];
        $skipped = 0;

        // Pre-load settings once (avoids 2 queries per order)
        $clinicName = \App\Models\Setting::get('clinic_name_ar', 'Doctorato Polyclinic');
        $clinicPhone = \App\Models\Setting::get('clinic_phone', '');

        foreach ($orders as $order) {
            $patient = $order->patient;
            if (! $patient || ! $patient->phone) {
                $skipped++;
                continue;
            }

            // Skip if already notified today
            if ($order->notification_sent_at && $order->notification_sent_at->isToday()) {
                $skipped++;
                continue;
            }

            // Build SMS message for this patient
            $itemType = $order->item_type ?? '';

            $smsBody = "مرحباً {$patient->full_name}\nنود إعلامكم أن طلبكم من المعمل جاهز"
                . ($itemType ? " ({$itemType})" : '') . ".\n"
                . "يرجى التواصل لتحديد موعد التركيب.\n"
                . ($clinicPhone ? "للحجز: {$clinicPhone}\n" : '')
                . $clinicName;

            $recipients[] = ['phone' => $patient->phone, 'message' => $smsBody];

            // Mark as notified immediately (job handles actual sending)
            $order->update(['notification_sent_at' => now()]);
        }

        if (count($recipients) > 0) {
            \App\Jobs\BulkSmsJob::dispatch($recipients, 'dental_lab_bulk_notify');
        }

        $queued = count($recipients);
        $message = "تم جدولة {$queued} رسالة للإرسال";
        if ($skipped > 0) {
            $message .= "، {$skipped} تم تخطيها";
        }

        return redirect()->back()->with('success', $message);
    }

    /**
     * Print view for selected lab orders.
     */
    public function printOrders(Request $request)
    {
        $data = $request->validate([
            'order_ids'   => 'required|string',
        ]);

        $ids = array_filter(explode(',', $data['order_ids']));
        if (empty($ids)) {
            return redirect()->back()->with('error', 'لا توجد طلبات محددة');
        }

        $orders = DentalLabOrder::whereIn('id', $ids)
            ->with([
                'patient:id,full_name,file_number,phone',
                'doctor:id,name_ar,name_en',
            ])
            ->orderBy('lab_name')
            ->orderBy('order_date')
            ->get();

        // Group by lab for easy printing
        $groupedByLab = $orders->groupBy(fn ($o) => $o->lab_name ?: 'غير محدد');

        $clinicName = Setting::get('clinic_name_ar', Setting::get('clinic_name_en', 'Doctorato Polyclinic'));
        $clinicPhone = Setting::get('clinic_phone', '');

        return Inertia::render('Admin/Dental/LabOrders/Print', [
            'groupedOrders' => $groupedByLab,
            'totalCount' => $orders->count(),
            'clinicName' => $clinicName,
            'clinicPhone' => $clinicPhone,
            'printDate' => now()->format('Y-m-d H:i'),
        ]);
    }
}
