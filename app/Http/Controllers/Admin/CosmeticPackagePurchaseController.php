<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CosmeticPackage;
use App\Models\CosmeticPackagePurchase;
use App\Models\Patient;
use App\Services\CosmeticDermaInvoiceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CosmeticPackagePurchaseController extends Controller
{
    public function __construct(private CosmeticDermaInvoiceService $invoicing) {}

    public function index(Request $request)
    {
        $query = CosmeticPackagePurchase::with([
            'patient:id,full_name,phone,file_number',
            'package:id,name_ar,name_en',
            'invoice:id,invoice_number,status,total,paid_amount',
        ]);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->whereHas('patient', fn ($q) => $q->where('full_name', 'like', "%$s%")
                ->orWhere('phone', 'like', "%$s%")
                ->orWhere('file_number', 'like', "%$s%"));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return Inertia::render('Admin/Cosmetic/Purchases/Index', [
            'purchases' => $query->latest()->paginate(20)->withQueryString(),
            'filters'   => $request->only(['search', 'status']),
            'packages'  => CosmeticPackage::where('is_active', true)
                ->orderBy('name_ar')
                ->get(['id', 'name_ar', 'name_en', 'total_sessions', 'total_price', 'validity_days']),
            'patients'  => Patient::orderBy('full_name')->limit(500)->get(['id', 'full_name', 'phone', 'file_number']),
            'stats'     => [
                'active'    => CosmeticPackagePurchase::where('status', 'active')->count(),
                'completed' => CosmeticPackagePurchase::where('status', 'completed')->count(),
                'revenue'   => (float) CosmeticPackagePurchase::sum('amount'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'patient_id' => 'required|exists:patients,id',
            'package_id' => 'required|exists:cosmetic_packages,id',
            'amount'     => 'nullable|numeric|min:0',
            'notes'      => 'nullable|string|max:1000',
        ]);

        $package = CosmeticPackage::findOrFail($data['package_id']);
        $this->invoicing->createPackagePurchase(
            $data['patient_id'],
            $package,
            $data['amount'] ?? null,
            $data['notes'] ?? null,
        );

        return back()->with('success', 'تم إنشاء الباقة وفاتورة الدفع المقدّم');
    }

    public function cancel(CosmeticPackagePurchase $purchase)
    {
        $purchase->update(['status' => CosmeticPackagePurchase::STATUS_CANCELLED]);

        return back()->with('success', 'تم إلغاء الباقة');
    }
}
