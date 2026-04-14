<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Models\BookingService;
use App\Models\Visit;
use App\Models\Invoice;
use App\Services\AuditLogger;
use App\Services\ModuleManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class ServiceCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = ServiceCategory::withCount('services')
            ->when($request->module, function ($query, $module) {
                $query->where('module', $module);
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name_en', 'like', "%{$search}%")
                      ->orWhere('name_ar', 'like', "%{$search}%");
                });
            })
            ->orderBy('display_order')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/ServiceCategories/Index', [
            'categories' => $categories,
            'filters' => $request->only(['search', 'module']),
            'modules' => ModuleManager::getForFrontend(),
        ]);
    }

    public function show(ServiceCategory $serviceCategory): Response
    {
        $serviceIds = $serviceCategory->services()->pluck('id');

        // ── Services with stats ──────────────────────────────
        $services = $serviceCategory->services()
            ->withCount('visits')
            ->get()
            ->map(function ($service) {
                $totalRevenue = Invoice::whereHas('visit', function ($q) use ($service) {
                    $q->where('service_id', $service->id);
                })->sum('total');

                $totalBookings = BookingService::where('service_id', $service->id)->count();
                $completedSessions = BookingService::where('service_id', $service->id)->sum('completed_sessions');

                return [
                    'id' => $service->id,
                    'name_en' => $service->name_en,
                    'name_ar' => $service->name_ar,
                    'image' => $service->image,
                    'status' => $service->status,
                    'price' => (float) $service->price,
                    'price_after_discount' => (float) $service->price_after_discount,
                    'supply_cost' => (float) $service->supply_cost,
                    'medical_fee' => (float) $service->medical_fee,
                    'doctor_commission_percentage' => (float) $service->doctor_commission_percentage,
                    'session_duration_minutes' => $service->session_duration_minutes,
                    'default_sessions' => $service->default_sessions,
                    'bookable' => $service->bookable,
                    'show_on_website' => $service->show_on_website,
                    'visits_count' => $service->visits_count,
                    'total_revenue' => $totalRevenue,
                    'total_bookings' => $totalBookings,
                    'completed_sessions' => $completedSessions,
                ];
            });

        // ── Summary Stats ────────────────────────────────────
        $totalServices = $services->count();
        $activeServices = $serviceCategory->services()->where('status', 'active')->count();
        $totalRevenue = $services->sum('total_revenue');
        $totalVisits = $services->sum('visits_count');
        $totalBookings = $services->sum('total_bookings');
        $avgPrice = $totalServices > 0 ? $services->avg('price') : 0;

        // ── Monthly Revenue (last 6 months) ──────────────────
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $revenue = Invoice::whereHas('visit', function ($q) use ($serviceIds) {
                $q->whereIn('service_id', $serviceIds);
            })
                ->whereYear('invoice_date', $date->year)
                ->whereMonth('invoice_date', $date->month)
                ->sum('total');

            $monthlyRevenue[] = [
                'month' => $date->format('M Y'),
                'short' => $date->format('M'),
                'revenue' => (float) $revenue,
            ];
        }

        // ── Monthly Visits (last 6 months) ───────────────────
        $monthlyVisits = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $count = Visit::whereIn('service_id', $serviceIds)
                ->whereYear('visit_date', $date->year)
                ->whereMonth('visit_date', $date->month)
                ->count();

            $monthlyVisits[] = [
                'month' => $date->format('M Y'),
                'short' => $date->format('M'),
                'count' => $count,
            ];
        }

        // ── Top Services by Revenue ──────────────────────────
        $topByRevenue = $services->sortByDesc('total_revenue')->take(5)->values();

        // ── Top Services by Visits ───────────────────────────
        $topByVisits = $services->sortByDesc('visits_count')->take(5)->values();

        // ── Recent Visits ────────────────────────────────────
        $recentVisits = Visit::whereIn('service_id', $serviceIds)
            ->with(['patient:id,name_en,name_ar', 'doctor.user:id,name', 'service:id,name_en'])
            ->latest('visit_date')
            ->take(10)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'visit_number' => $v->visit_number,
                'patient_name' => $v->patient?->name_en,
                'doctor_name' => $v->doctor?->user?->name,
                'service_name' => $v->service?->name_en,
                'visit_date' => $v->visit_date?->format('M d, Y'),
                'status' => $v->status,
                'commission_amount' => (float) $v->commission_amount,
            ]);

        // ── Revenue Share per Service (for pie chart) ────────
        $revenueShare = $services->filter(fn ($s) => $s['total_revenue'] > 0)
            ->map(fn ($s) => [
                'name' => $s['name_en'],
                'revenue' => $s['total_revenue'],
            ])
            ->sortByDesc('revenue')
            ->take(8)
            ->values();

        return Inertia::render('Admin/ServiceCategories/Show', [
            'category' => $serviceCategory,
            'services' => $services->values(),
            'stats' => [
                'totalServices' => $totalServices,
                'activeServices' => $activeServices,
                'totalRevenue' => (float) $totalRevenue,
                'totalVisits' => $totalVisits,
                'totalBookings' => $totalBookings,
                'avgPrice' => round($avgPrice, 2),
            ],
            'monthlyRevenue' => $monthlyRevenue,
            'monthlyVisits' => $monthlyVisits,
            'topByRevenue' => $topByRevenue,
            'topByVisits' => $topByVisits,
            'recentVisits' => $recentVisits,
            'revenueShare' => $revenueShare,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/ServiceCategories/Create', [
            'modules' => ModuleManager::getForFrontend(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'module' => 'nullable|string|in:derma,dental,pediatric',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'display_order' => 'nullable|integer',
        ]);

        $data['slug'] = Str::slug($data['name_en']);

        $serviceCategory = ServiceCategory::create($data);

        AuditLogger::log('created', $serviceCategory);

        return redirect()->route('admin.service-categories.index')->with('success', 'Service category created successfully.');
    }

    public function edit(ServiceCategory $serviceCategory): Response
    {
        return Inertia::render('Admin/ServiceCategories/Edit', [
            'category' => $serviceCategory,
            'modules' => ModuleManager::getForFrontend(),
        ]);
    }

    public function update(Request $request, ServiceCategory $serviceCategory): RedirectResponse
    {
        $data = $request->validate([
            'module' => 'nullable|string|in:derma,dental,pediatric',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'display_order' => 'nullable|integer',
        ]);

        $data['slug'] = Str::slug($data['name_en']);

        $serviceCategory->update($data);

        AuditLogger::log('updated', $serviceCategory);

        return redirect()->route('admin.service-categories.index')->with('success', 'Service category updated successfully.');
    }

    public function destroy(ServiceCategory $serviceCategory): RedirectResponse
    {
        AuditLogger::log('deleted', $serviceCategory);
        $serviceCategory->delete();

        return redirect()->route('admin.service-categories.index')->with('success', 'Service category deleted successfully.');
    }
}
