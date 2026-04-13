<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supply;
use App\Models\SupplyCategory;
use App\Models\SupplyTransaction;
use App\Services\AuditLogger;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SupplyController extends Controller
{
    public function dashboard(Request $request): Response
    {
        $module = $request->input('module', 'all');
        $baseQuery = fn () => Supply::when($module !== 'all', fn ($q) => $q->forModule($module));

        $totalProducts = $baseQuery()->count();
        $activeProducts = $baseQuery()->active()->count();
        $lowStockCount = $baseQuery()->lowStock()->count();
        $expiringSoonCount = $baseQuery()->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::now()->addDays(30))
            ->where('expiry_date', '>=', Carbon::now())
            ->count();
        $expiredCount = $baseQuery()->whereNotNull('expiry_date')
            ->where('expiry_date', '<', Carbon::now())
            ->count();

        $totalValue = $baseQuery()->active()->sum(DB::raw('quantity * purchase_price'));

        // Get supply IDs for module-filtered transaction queries
        $supplyIds = $module !== 'all' ? $baseQuery()->pluck('id') : null;

        // Category distribution
        $categoryDistribution = SupplyCategory::withCount(['supplies' => function ($q) use ($module) {
            $q->active();
            if ($module !== 'all') {
                $q->forModule($module);
            }
        }])
            ->ordered()
            ->get()
            ->map(fn ($c) => [
                'name' => $c->name_en,
                'color' => $c->color,
                'count' => $c->supplies_count,
            ]);

        // Low stock items
        $lowStockItems = $baseQuery()->lowStock()
            ->with('supplyCategory')
            ->orderByRaw('quantity - min_quantity ASC')
            ->limit(10)
            ->get();

        // Expiring soon items
        $expiringSoonItems = $baseQuery()->whereNotNull('expiry_date')
            ->where('expiry_date', '<=', Carbon::now()->addDays(30))
            ->where('expiry_date', '>=', Carbon::now())
            ->with('supplyCategory')
            ->orderBy('expiry_date')
            ->limit(10)
            ->get();

        // Recent transactions
        $recentTransactions = SupplyTransaction::with(['supply', 'creator'])
            ->when($supplyIds, fn ($q) => $q->whereIn('supply_id', $supplyIds))
            ->latest()
            ->limit(10)
            ->get();

        // Top used supplies (most usage transactions in last 30 days)
        $topUsed = SupplyTransaction::where('transaction_type', 'usage')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->when($supplyIds, fn ($q) => $q->whereIn('supply_id', $supplyIds))
            ->select('supply_id', DB::raw('SUM(quantity) as total_used'))
            ->groupBy('supply_id')
            ->orderByDesc('total_used')
            ->limit(5)
            ->with('supply')
            ->get();

        // Monthly transaction trends (last 6 months)
        $monthlyTrends = SupplyTransaction::where('created_at', '>=', Carbon::now()->subMonths(6))
            ->when($supplyIds, fn ($q) => $q->whereIn('supply_id', $supplyIds))
            ->select(
                DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                'transaction_type',
                DB::raw('SUM(quantity) as total')
            )
            ->groupBy('month', 'transaction_type')
            ->orderBy('month')
            ->get()
            ->groupBy('month')
            ->map(fn ($items) => [
                'purchases' => $items->where('transaction_type', 'purchase')->sum('total'),
                'usage' => $items->where('transaction_type', 'usage')->sum('total'),
            ]);

        return Inertia::render('Admin/Supplies/Dashboard', [
            'stats' => [
                'totalProducts' => $totalProducts,
                'activeProducts' => $activeProducts,
                'lowStockCount' => $lowStockCount,
                'expiringSoonCount' => $expiringSoonCount,
                'expiredCount' => $expiredCount,
                'totalValue' => round($totalValue, 2),
            ],
            'categoryDistribution' => $categoryDistribution,
            'lowStockItems' => $lowStockItems,
            'expiringSoonItems' => $expiringSoonItems,
            'recentTransactions' => $recentTransactions,
            'topUsed' => $topUsed,
            'monthlyTrends' => $monthlyTrends,
            'activeModule' => $module,
        ]);
    }

    public function index(Request $request): Response
    {
        $query = Supply::with('supplyCategory');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->input('stock') === 'low') {
            $query->lowStock();
        } elseif ($request->input('stock') === 'expired') {
            $query->whereNotNull('expiry_date')->where('expiry_date', '<', Carbon::now());
        } elseif ($request->input('stock') === 'expiring') {
            $query->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', Carbon::now()->addDays(30))
                ->where('expiry_date', '>=', Carbon::now());
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('supply_category_id', $categoryId);
        }

        if ($module = $request->input('module')) {
            $query->forModule($module);
        }

        $supplies = $query->latest()->paginate(15)->withQueryString();

        $categories = SupplyCategory::active()->ordered()->get();

        return Inertia::render('Admin/Supplies/Index', [
            'supplies' => $supplies,
            'categories' => $categories,
            'filters' => $request->only(['search', 'status', 'stock', 'category_id', 'module']),
        ]);
    }

    public function create(): Response
    {
        $categories = SupplyCategory::active()->ordered()->get();

        return Inertia::render('Admin/Supplies/Create', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'module' => 'nullable|string|in:derma,dental,shared',
            'sku' => 'nullable|string|max:100|unique:supplies,sku',
            'barcode' => 'nullable|string|max:100|unique:supplies,barcode',
            'supply_category_id' => 'nullable|exists:supply_categories,id',
            'category' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:50',
            'quantity' => 'required|numeric|min:0',
            'min_quantity' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('supplies', 'public');
        }

        $supply = Supply::create($data);

        // Create initial stock transaction if quantity > 0
        if ($supply->quantity > 0) {
            SupplyTransaction::create([
                'supply_id' => $supply->id,
                'transaction_type' => 'purchase',
                'quantity' => $supply->quantity,
                'unit_cost' => $supply->purchase_price ?? 0,
                'notes' => 'Initial stock',
                'created_by' => auth()->id(),
            ]);
        }

        AuditLogger::log('created', $supply);

        return redirect()->route('admin.supplies.index')->with('success', 'Supply created successfully.');
    }

    public function show(Supply $supply): Response
    {
        $supply->load('supplyCategory', 'serviceSupplies.service');

        $transactions = $supply->transactions()
            ->with('creator')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        // Stock level percentage
        $maxStock = $supply->min_quantity > 0 ? max($supply->quantity, $supply->min_quantity * 3) : max($supply->quantity, 100);
        $stockPercentage = $maxStock > 0 ? min(round(($supply->quantity / $maxStock) * 100), 100) : 0;

        return Inertia::render('Admin/Supplies/Show', [
            'supply' => $supply,
            'transactions' => $transactions,
            'stockPercentage' => $stockPercentage,
        ]);
    }

    public function edit(Supply $supply): Response
    {
        $categories = SupplyCategory::active()->ordered()->get();

        return Inertia::render('Admin/Supplies/Edit', [
            'supply' => $supply,
            'categories' => $categories,
        ]);
    }

    public function update(Request $request, Supply $supply): RedirectResponse
    {
        $data = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string|max:255',
            'module' => 'nullable|string|in:derma,dental,shared',
            'sku' => 'nullable|string|max:100|unique:supplies,sku,' . $supply->id,
            'barcode' => 'nullable|string|max:100|unique:supplies,barcode,' . $supply->id,
            'supply_category_id' => 'nullable|exists:supply_categories,id',
            'category' => 'nullable|string|max:100',
            'unit' => 'nullable|string|max:50',
            'min_quantity' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'supplier' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('supplies', 'public');
        }

        $supply->update($data);

        AuditLogger::log('updated', $supply);

        return redirect()->route('admin.supplies.index')->with('success', 'Supply updated successfully.');
    }

    public function destroy(Supply $supply): RedirectResponse
    {
        AuditLogger::log('deleted', $supply);

        $supply->delete();

        return redirect()->route('admin.supplies.index')->with('success', 'Supply deleted successfully.');
    }

    public function transactions(Supply $supply): Response
    {
        $transactions = $supply->transactions()
            ->with('creator')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/Supplies/Transactions', [
            'supply' => $supply,
            'transactions' => $transactions,
        ]);
    }

    public function addTransaction(Request $request, Supply $supply): RedirectResponse
    {
        $data = $request->validate([
            'transaction_type' => 'required|in:purchase,usage,adjustment,return',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $data['supply_id'] = $supply->id;
        $data['created_by'] = auth()->id();

        SupplyTransaction::create($data);

        // Update supply quantity
        if (in_array($data['transaction_type'], ['purchase', 'return'])) {
            $supply->increment('quantity', $data['quantity']);
        } else {
            $supply->decrement('quantity', $data['quantity']);
        }

        AuditLogger::log('transaction_added', $supply);

        return redirect()->back()->with('success', 'Transaction recorded successfully.');
    }
}
