<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Models\Supply;
use App\Models\SupplyCategory;
use App\Models\SupplyTransaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SecretaryInventoryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Supply::active()->with('supplyCategory');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($module = $request->input('module')) {
            $query->forModule($module);
        }

        if ($categoryId = $request->input('category_id')) {
            $query->where('supply_category_id', $categoryId);
        }

        if ($request->input('stock') === 'low') {
            $query->lowStock();
        }

        $supplies = $query->orderBy('name_en')->paginate(20)->withQueryString();

        $categories = SupplyCategory::active()->ordered()->get();

        $stats = [
            'total' => Supply::active()->count(),
            'lowStock' => Supply::active()->lowStock()->count(),
            'expiringSoon' => Supply::active()
                ->whereNotNull('expiry_date')
                ->where('expiry_date', '<=', Carbon::now()->addDays(30))
                ->where('expiry_date', '>=', Carbon::now())
                ->count(),
        ];

        return Inertia::render('Secretary/Inventory/Index', [
            'supplies' => $supplies,
            'categories' => $categories,
            'filters' => $request->only(['search', 'module', 'category_id', 'stock']),
            'stats' => $stats,
        ]);
    }

    public function quickAdjust(Request $request, Supply $supply): RedirectResponse
    {
        $data = $request->validate([
            'transaction_type' => 'required|in:usage,adjustment',
            'quantity' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        SupplyTransaction::create([
            'supply_id' => $supply->id,
            'transaction_type' => $data['transaction_type'],
            'quantity' => $data['quantity'],
            'notes' => $data['notes'] ?? 'Secretary quick adjustment',
            'created_by' => auth()->id(),
        ]);

        if ($data['transaction_type'] === 'usage') {
            $supply->decrement('quantity', $data['quantity']);
        } else {
            $supply->increment('quantity', $data['quantity']);
        }

        $locale = $request->header('X-Locale', app()->getLocale());
        $msg = $locale === 'ar' ? 'تم تسجيل الحركة بنجاح.' : 'Transaction recorded successfully.';

        return redirect()->back()->with('success', $msg);
    }
}
