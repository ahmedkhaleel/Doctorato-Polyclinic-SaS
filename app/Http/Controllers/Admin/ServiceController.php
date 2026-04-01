<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\BookingService;
use App\Models\Visit;
use App\Models\Invoice;
use App\Models\DoctorServiceRate;
use App\Services\AuditLogger;
use App\Services\ModuleManager;
use App\Traits\SanitizesHtml;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    use SanitizesHtml;

    public function index(Request $request): Response
    {
        $services = Service::with('category')
            ->whereIn('module', ['derma', 'dental'])
            ->when($request->module, function ($query, $module) {
                $query->where('module', $module);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name_ar', 'like', "%{$search}%")
                      ->orWhere('name_en', 'like', "%{$search}%");
                });
            })
            ->when($request->category_id, function ($query, $categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('display_order')
            ->paginate(15)
            ->withQueryString();

        $categories = ServiceCategory::all();

        return Inertia::render('Admin/Services/Index', [
            'services' => $services,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'module']),
            'modules' => collect(ModuleManager::getForFrontend())->only(['derma', 'dental'])->all(),
        ]);
    }

    public function show(Request $request, Service $service): Response
    {
        $service->load('category');

        // ── Date range filter ───────────────────────────────
        $dateFrom = $request->date_from ? Carbon::parse($request->date_from)->startOfDay() : null;
        $dateTo = $request->date_to ? Carbon::parse($request->date_to)->endOfDay() : null;

        $dateFilter = function ($query, $dateColumn = 'visit_date') use ($dateFrom, $dateTo) {
            if ($dateFrom) $query->where($dateColumn, '>=', $dateFrom);
            if ($dateTo) $query->where($dateColumn, '<=', $dateTo);
        };

        // ── Summary Stats ───────────────────────────────────
        $visitsQuery = Visit::where('service_id', $service->id);
        if ($dateFrom || $dateTo) $dateFilter($visitsQuery);

        $totalVisits = (clone $visitsQuery)->count();
        $completedVisits = (clone $visitsQuery)->where('status', 'completed')->count();
        $totalCommission = (clone $visitsQuery)->sum('commission_amount');

        $invoiceQuery = Invoice::whereHas('visit', function ($q) use ($service, $dateFrom, $dateTo) {
            $q->where('service_id', $service->id);
            if ($dateFrom) $q->where('visit_date', '>=', $dateFrom);
            if ($dateTo) $q->where('visit_date', '<=', $dateTo);
        });
        $totalRevenue = (clone $invoiceQuery)->sum('total');
        $totalPaid = (clone $invoiceQuery)->sum('paid_amount');

        $bookingServiceQuery = BookingService::where('service_id', $service->id);
        $totalBookings = (clone $bookingServiceQuery)->count();
        $totalSessions = (clone $bookingServiceQuery)->sum('sessions_count');
        $completedSessions = (clone $bookingServiceQuery)->sum('completed_sessions');

        $stats = [
            'totalRevenue' => (float) $totalRevenue,
            'totalPaid' => (float) $totalPaid,
            'totalVisits' => $totalVisits,
            'completedVisits' => $completedVisits,
            'totalCommission' => (float) $totalCommission,
            'totalBookings' => $totalBookings,
            'totalSessions' => $totalSessions,
            'completedSessions' => $completedSessions,
            'completionRate' => $totalVisits > 0 ? round(($completedVisits / $totalVisits) * 100) : 0,
            'avgRevenuePerVisit' => $totalVisits > 0 ? round($totalRevenue / $totalVisits, 2) : 0,
        ];

        // ── Monthly Revenue & Visits (last 12 months) ───────
        $monthlyData = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);

            $monthRevenue = Invoice::whereHas('visit', function ($q) use ($service, $date) {
                $q->where('service_id', $service->id);
            })
                ->whereYear('invoice_date', $date->year)
                ->whereMonth('invoice_date', $date->month)
                ->sum('total');

            $monthVisits = Visit::where('service_id', $service->id)
                ->whereYear('visit_date', $date->year)
                ->whereMonth('visit_date', $date->month)
                ->count();

            $monthCommission = Visit::where('service_id', $service->id)
                ->whereYear('visit_date', $date->year)
                ->whereMonth('visit_date', $date->month)
                ->sum('commission_amount');

            $monthlyData[] = [
                'month' => $date->format('M Y'),
                'short' => $date->format('M'),
                'revenue' => (float) $monthRevenue,
                'visits' => $monthVisits,
                'commission' => (float) $monthCommission,
            ];
        }

        // ── Doctor Performance ──────────────────────────────
        $doctorPerformance = Visit::where('service_id', $service->id)
            ->select('doctor_id', DB::raw('COUNT(*) as visit_count'), DB::raw('SUM(commission_amount) as total_commission'))
            ->when($dateFrom, fn ($q) => $q->where('visit_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('visit_date', '<=', $dateTo))
            ->groupBy('doctor_id')
            ->with('doctor.user:id,name')
            ->orderByDesc('visit_count')
            ->get()
            ->map(fn ($v) => [
                'doctor_name' => $v->doctor?->user?->name ?? 'Unknown',
                'visit_count' => $v->visit_count,
                'total_commission' => (float) $v->total_commission,
            ]);

        // ── Doctor Rates ────────────────────────────────────
        $doctorRates = DoctorServiceRate::where('service_id', $service->id)
            ->with('doctor.user:id,name')
            ->get()
            ->map(fn ($r) => [
                'doctor_name' => $r->doctor?->user?->name ?? 'Unknown',
                'commission_percentage' => (float) $r->commission_percentage,
            ]);

        // ── Visit Status Distribution ───────────────────────
        $statusDistribution = Visit::where('service_id', $service->id)
            ->when($dateFrom, fn ($q) => $q->where('visit_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('visit_date', '<=', $dateTo))
            ->select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // ── Recent Visits (paginated timeline) ──────────────
        $recentVisits = Visit::where('service_id', $service->id)
            ->with([
                'patient:id,full_name,phone',
                'doctor:id,name_en,name_ar',
                'invoice:id,visit_id,total,paid_amount,status',
            ])
            ->when($dateFrom, fn ($q) => $q->where('visit_date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('visit_date', '<=', $dateTo))
            ->latest('visit_date')
            ->take(20)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'visit_number' => $v->visit_number ?? null,
                'patient_name' => $v->patient?->full_name,
                'patient_phone' => $v->patient?->phone,
                'doctor_name' => $v->doctor?->name_en,
                'session_number' => $v->session_number,
                'visit_date' => $v->visit_date?->format('M d, Y'),
                'visit_date_raw' => $v->visit_date?->toDateString(),
                'status' => $v->status,
                'commission_amount' => (float) $v->commission_amount,
                'invoice_total' => $v->invoice ? (float) $v->invoice->total : null,
                'invoice_status' => $v->invoice?->status,
                'started_at' => $v->started_at?->format('h:i A'),
                'completed_at' => $v->completed_at?->format('h:i A'),
            ]);

        // ── Recent Bookings ─────────────────────────────────
        $recentBookings = BookingService::where('service_id', $service->id)
            ->with([
                'booking:id,booking_number,full_name,phone,status,created_at',
                'doctor.user:id,name',
            ])
            ->latest('created_at')
            ->take(10)
            ->get()
            ->map(fn ($bs) => [
                'id' => $bs->id,
                'booking_number' => $bs->booking?->booking_number,
                'patient_name' => $bs->booking?->full_name,
                'doctor_name' => $bs->doctor?->user?->name,
                'sessions_count' => $bs->sessions_count,
                'completed_sessions' => $bs->completed_sessions,
                'remaining_sessions' => $bs->remaining_sessions,
                'unit_price' => (float) $bs->unit_price,
                'total_price' => (float) $bs->total_price,
                'status' => $bs->booking?->status,
                'created_at' => $bs->created_at?->format('M d, Y'),
            ]);

        // ── Price Breakdown ─────────────────────────────────
        $priceInfo = [
            'price' => (float) $service->price,
            'price_after_discount' => (float) $service->price_after_discount,
            'supply_cost' => (float) $service->supply_cost,
            'medical_fee' => (float) $service->medical_fee,
            'doctor_commission_percentage' => (float) $service->doctor_commission_percentage,
            'profit_margin' => $service->price > 0
                ? round((($service->price - ($service->supply_cost ?? 0)) / $service->price) * 100, 1)
                : 0,
        ];

        return Inertia::render('Admin/Services/Show', [
            'service' => $service,
            'stats' => $stats,
            'monthlyData' => $monthlyData,
            'doctorPerformance' => $doctorPerformance,
            'doctorRates' => $doctorRates,
            'statusDistribution' => $statusDistribution,
            'recentVisits' => $recentVisits,
            'recentBookings' => $recentBookings,
            'priceInfo' => $priceInfo,
            'filters' => $request->only(['date_from', 'date_to']),
        ]);
    }

    public function create(): Response
    {
        $categories = ServiceCategory::orderBy('display_order')->get();

        return Inertia::render('Admin/Services/Create', [
            'categories' => $categories,
            'modules' => collect(ModuleManager::getForFrontend())->only(['derma', 'dental'])->all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'module' => 'nullable|string|in:derma,dental',
            'category_id' => 'required|exists:service_categories,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:services,slug',
            'short_desc_ar' => 'nullable|string',
            'short_desc_en' => 'nullable|string',
            'full_desc_ar' => 'nullable|string',
            'full_desc_en' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:20480',
            'benefits_ar' => 'nullable|string',
            'benefits_en' => 'nullable|string',
            'sessions_count' => 'nullable|integer',
            'results_ar' => 'nullable|string',
            'results_en' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'show_on_home' => 'boolean',
            'show_on_website' => 'boolean',
            'bookable' => 'boolean',
            'seo_title_ar' => 'nullable|string|max:255',
            'seo_title_en' => 'nullable|string|max:255',
            'seo_desc_ar' => 'nullable|string',
            'seo_desc_en' => 'nullable|string',
            // Pricing & Clinic fields
            'price' => 'nullable|numeric|min:0',
            'price_after_discount' => 'nullable|numeric|min:0',
            'supply_cost' => 'nullable|numeric|min:0',
            'medical_fee' => 'nullable|numeric|min:0',

            'default_sessions' => 'nullable|integer|min:1',
            'session_duration_minutes' => 'nullable|integer|min:1',
            'clinic_notes' => 'nullable|string|max:1000',
        ]);

        $this->sanitizeFields($data, ['full_desc_ar', 'full_desc_en']);

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $this->processAndStoreImage($request->file('featured_image'), 'uploads/services');
        }

        $data['slug'] = $data['slug'] ?? Str::slug($data['name_en']);

        $service = Service::create($data);

        AuditLogger::log('created', $service);

        return redirect()->route('admin.services.index')->with('success', 'Service created successfully.');
    }

    public function edit(Service $service): Response
    {
        $service->load(['category', 'faqs', 'gallery']);
        $categories = ServiceCategory::orderBy('display_order')->get();

        return Inertia::render('Admin/Services/Edit', [
            'service' => $service,
            'categories' => $categories,
            'modules' => collect(ModuleManager::getForFrontend())->only(['derma', 'dental'])->all(),
        ]);
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $request->validate([
            'module' => 'nullable|string|in:derma,dental',
            'category_id' => 'required|exists:service_categories,id',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:services,slug,' . $service->id,
            'short_desc_ar' => 'nullable|string',
            'short_desc_en' => 'nullable|string',
            'full_desc_ar' => 'nullable|string',
            'full_desc_en' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:20480',
            'benefits_ar' => 'nullable|string',
            'benefits_en' => 'nullable|string',
            'sessions_count' => 'nullable|integer',
            'results_ar' => 'nullable|string',
            'results_en' => 'nullable|string',
            'display_order' => 'nullable|integer',
            'status' => 'required|in:active,inactive',
            'show_on_home' => 'boolean',
            'show_on_website' => 'boolean',
            'bookable' => 'boolean',
            'seo_title_ar' => 'nullable|string|max:255',
            'seo_title_en' => 'nullable|string|max:255',
            'seo_desc_ar' => 'nullable|string',
            'seo_desc_en' => 'nullable|string',
            // Pricing & Clinic fields
            'price' => 'nullable|numeric|min:0',
            'price_after_discount' => 'nullable|numeric|min:0',
            'supply_cost' => 'nullable|numeric|min:0',
            'medical_fee' => 'nullable|numeric|min:0',

            'default_sessions' => 'nullable|integer|min:1',
            'session_duration_minutes' => 'nullable|integer|min:1',
            'clinic_notes' => 'nullable|string|max:1000',
        ]);

        $this->sanitizeFields($data, ['full_desc_ar', 'full_desc_en']);

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $this->processAndStoreImage($request->file('featured_image'), 'uploads/services');
        }

        if (isset($data['slug'])) {
            $data['slug'] = $data['slug'] ?: Str::slug($data['name_en']);
        }

        $service->update($data);

        AuditLogger::log('updated', $service);

        return redirect()->route('admin.services.index')->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        AuditLogger::log('deleted', $service);
        $service->faqs()->delete();
        $service->gallery()->delete();
        $service->delete();

        return redirect()->route('admin.services.index')->with('success', 'Service deleted successfully.');
    }

    /**
     * Process, compress, and store an uploaded image.
     */
    protected function processAndStoreImage(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        // For SVG files, store directly without processing
        if ($extension === 'svg') {
            return $file->store($directory, 'public');
        }

        // Generate unique filename
        $filename = Str::uuid() . '.webp';
        $storagePath = $directory . '/' . $filename;

        // Read image and compress it
        $image = $file->getContent();
        $gdImage = null;

        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $gdImage = @imagecreatefromjpeg($file->getRealPath());
                break;
            case 'png':
                $gdImage = @imagecreatefrompng($file->getRealPath());
                break;
            case 'webp':
                $gdImage = @imagecreatefromwebp($file->getRealPath());
                break;
        }

        // If GD processing fails, store original file
        if (!$gdImage) {
            return $file->store($directory, 'public');
        }

        // Get original dimensions
        $origWidth = imagesx($gdImage);
        $origHeight = imagesy($gdImage);

        // Resize if larger than 1920px on any side
        $maxDimension = 1920;
        if ($origWidth > $maxDimension || $origHeight > $maxDimension) {
            $ratio = min($maxDimension / $origWidth, $maxDimension / $origHeight);
            $newWidth = (int) round($origWidth * $ratio);
            $newHeight = (int) round($origHeight * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG
            imagealphablending($resized, false);
            imagesavealpha($resized, true);

            imagecopyresampled($resized, $gdImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
            imagedestroy($gdImage);
            $gdImage = $resized;
        }

        // Save as WebP with 85% quality (good balance of quality and size)
        ob_start();
        imagewebp($gdImage, null, 85);
        $webpData = ob_get_clean();
        imagedestroy($gdImage);

        // Store the processed image
        Storage::disk('public')->put($storagePath, $webpData);

        return $storagePath;
    }
}
