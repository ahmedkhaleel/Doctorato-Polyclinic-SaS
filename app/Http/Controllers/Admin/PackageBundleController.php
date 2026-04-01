<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackageBundle;
use App\Models\Service;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PackageBundleController extends Controller
{
    public function index(Request $request): Response
    {
        $query = PackageBundle::withCount(['services', 'bundleBookings']);

        if ($module = $request->input('module')) {
            $query->whereHas('services.service', fn ($q) => $q->where('module', $module));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->input('status') !== '') {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $bundles = $query->orderBy('display_order')->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/PackageBundles/Index', [
            'bundles' => $bundles,
            'filters' => $request->only(['search', 'status', 'module']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/PackageBundles/Create', [
            'services' => Service::active()
                ->select('id', 'name_ar', 'name_en', 'price', 'supply_cost', 'doctor_commission_percentage')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'total_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
            'display_order' => 'nullable|integer|min:0',
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.sessions_count' => 'required|integer|min:1',
            'services.*.discount_percentage' => 'required|numeric|min:0|max:100',
            'services.*.bundle_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($data, $request) {
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('package-bundles', 'public');
            }

            // Calculate original price (sum of service prices * sessions)
            $originalPrice = 0;
            foreach ($data['services'] as $svc) {
                $service = Service::find($svc['service_id']);
                if ($service) {
                    $originalPrice += $service->price * $svc['sessions_count'];
                }
            }

            $bundle = PackageBundle::create([
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'],
                'description_ar' => $data['description_ar'] ?? null,
                'description_en' => $data['description_en'] ?? null,
                'total_price' => $data['total_price'],
                'original_price' => $originalPrice,
                'is_active' => $data['is_active'] ?? true,
                'image' => $imagePath,
                'display_order' => $data['display_order'] ?? 0,
            ]);

            foreach ($data['services'] as $index => $svc) {
                $bundle->services()->create([
                    'service_id' => $svc['service_id'],
                    'sessions_count' => $svc['sessions_count'],
                    'discount_percentage' => $svc['discount_percentage'],
                    'bundle_price' => $svc['bundle_price'],
                    'display_order' => $index,
                ]);
            }

            AuditLogger::log('created', $bundle);
        });

        return redirect()->route('admin.package-bundles.index')->with('success', 'Package bundle created successfully.');
    }

    public function show(PackageBundle $packageBundle): Response
    {
        $packageBundle->load([
            'services.service',
            'bundleBookings' => fn ($q) => $q->with(['patient', 'booking'])->latest()->limit(20),
        ]);

        return Inertia::render('Admin/PackageBundles/Show', [
            'bundle' => $packageBundle,
        ]);
    }

    public function edit(PackageBundle $packageBundle): Response
    {
        $packageBundle->load('services');

        return Inertia::render('Admin/PackageBundles/Edit', [
            'bundle' => $packageBundle,
            'services' => Service::active()
                ->select('id', 'name_ar', 'name_en', 'price', 'supply_cost', 'doctor_commission_percentage')
                ->get(),
        ]);
    }

    public function update(Request $request, PackageBundle $packageBundle): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'description_ar' => 'nullable|string',
            'description_en' => 'nullable|string',
            'total_price' => 'required|numeric|min:0',
            'is_active' => 'boolean',
            'image' => 'nullable|image|max:2048',
            'display_order' => 'nullable|integer|min:0',
            'services' => 'required|array|min:1',
            'services.*.service_id' => 'required|exists:services,id',
            'services.*.sessions_count' => 'required|integer|min:1',
            'services.*.discount_percentage' => 'required|numeric|min:0|max:100',
            'services.*.bundle_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($data, $request, $packageBundle) {
            $imagePath = $packageBundle->image;
            if ($request->hasFile('image')) {
                if ($packageBundle->image) {
                    Storage::disk('public')->delete($packageBundle->image);
                }
                $imagePath = $request->file('image')->store('package-bundles', 'public');
            }

            $originalPrice = 0;
            foreach ($data['services'] as $svc) {
                $service = Service::find($svc['service_id']);
                if ($service) {
                    $originalPrice += $service->price * $svc['sessions_count'];
                }
            }

            $packageBundle->update([
                'name_ar' => $data['name_ar'],
                'name_en' => $data['name_en'],
                'description_ar' => $data['description_ar'] ?? null,
                'description_en' => $data['description_en'] ?? null,
                'total_price' => $data['total_price'],
                'original_price' => $originalPrice,
                'is_active' => $data['is_active'] ?? true,
                'image' => $imagePath,
                'display_order' => $data['display_order'] ?? 0,
            ]);

            // Sync services: delete old, create new
            $packageBundle->services()->delete();
            foreach ($data['services'] as $index => $svc) {
                $packageBundle->services()->create([
                    'service_id' => $svc['service_id'],
                    'sessions_count' => $svc['sessions_count'],
                    'discount_percentage' => $svc['discount_percentage'],
                    'bundle_price' => $svc['bundle_price'],
                    'display_order' => $index,
                ]);
            }

            AuditLogger::log('updated', $packageBundle);
        });

        return redirect()->route('admin.package-bundles.index')->with('success', 'Package bundle updated successfully.');
    }

    public function destroy(PackageBundle $packageBundle): RedirectResponse
    {
        // Block deletion if there are active bookings
        $activeBookings = $packageBundle->bundleBookings()
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->count();

        if ($activeBookings > 0) {
            return redirect()->back()->with('error', 'Cannot delete this bundle because it has active bookings.');
        }

        if ($packageBundle->image) {
            Storage::disk('public')->delete($packageBundle->image);
        }

        $packageBundle->delete();

        AuditLogger::log('deleted', $packageBundle);

        return redirect()->route('admin.package-bundles.index')->with('success', 'Package bundle deleted successfully.');
    }

    public function toggleActive(PackageBundle $packageBundle): RedirectResponse
    {
        $packageBundle->update(['is_active' => !$packageBundle->is_active]);

        $status = $packageBundle->is_active ? 'activated' : 'deactivated';
        AuditLogger::log($status, $packageBundle);

        return redirect()->back()->with('success', "Package bundle {$status} successfully.");
    }
}
