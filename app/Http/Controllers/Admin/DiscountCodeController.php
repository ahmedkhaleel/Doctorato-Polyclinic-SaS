<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DiscountCode;
use App\Models\PackageBundle;
use App\Models\Service;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DiscountCodeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DiscountCode::with('creator');

        if ($search = $request->input('search')) {
            $query->where('code', 'like', "%{$search}%");
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        if ($request->input('status') === 'popup') {
            $query->where('show_on_website', true);
        }

        $discountCodes = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Admin/DiscountCodes/Index', [
            'discountCodes' => $discountCodes,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/DiscountCodes/Create', [
            'services' => Service::active()->select('id', 'name_ar', 'name_en')->get(),
            'packageBundles' => PackageBundle::active()->select('id', 'name_ar', 'name_en', 'total_price')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:discount_codes,code',
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'applicable_services' => 'nullable|array',
            'applicable_services.*' => 'exists:services,id',
            'applicable_packages' => 'nullable|array',
            'applicable_packages.*' => 'exists:package_bundles,id',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'per_patient_limit' => 'nullable|integer|min:1',
            'first_booking_only' => 'boolean',
            'show_on_website' => 'boolean',
            'popup_title_en' => 'nullable|string|max:255',
            'popup_title_ar' => 'nullable|string|max:255',
            'popup_description_en' => 'nullable|string|max:1000',
            'popup_description_ar' => 'nullable|string|max:1000',
            'popup_image' => 'nullable|image|max:2048',
            'notes' => 'nullable|string',
        ]);

        // Handle popup image upload
        if ($request->hasFile('popup_image')) {
            $data['popup_image'] = $request->file('popup_image')->store('discount-codes', 'public');
        }

        $data['used_count'] = 0;
        $data['created_by'] = auth()->id();

        $discountCode = DiscountCode::create($data);

        AuditLogger::log('created', $discountCode);

        return redirect()->route('admin.discount-codes.index')->with('success', 'Discount code created successfully.');
    }

    public function edit(DiscountCode $discountCode): Response
    {
        return Inertia::render('Admin/DiscountCodes/Edit', [
            'discountCode' => $discountCode,
            'services' => Service::active()->select('id', 'name_ar', 'name_en')->get(),
            'packageBundles' => PackageBundle::active()->select('id', 'name_ar', 'name_en', 'total_price')->get(),
        ]);
    }

    public function update(Request $request, DiscountCode $discountCode): RedirectResponse
    {
        $data = $request->validate([
            'code' => 'required|string|max:50|unique:discount_codes,code,' . $discountCode->id,
            'discount_type' => 'required|in:percentage,fixed',
            'discount_value' => 'required|numeric|min:0',
            'max_uses' => 'nullable|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
            'applicable_services' => 'nullable|array',
            'applicable_services.*' => 'exists:services,id',
            'applicable_packages' => 'nullable|array',
            'applicable_packages.*' => 'exists:package_bundles,id',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'per_patient_limit' => 'nullable|integer|min:1',
            'first_booking_only' => 'boolean',
            'show_on_website' => 'boolean',
            'popup_title_en' => 'nullable|string|max:255',
            'popup_title_ar' => 'nullable|string|max:255',
            'popup_description_en' => 'nullable|string|max:1000',
            'popup_description_ar' => 'nullable|string|max:1000',
            'popup_image' => 'nullable|image|max:2048',
            'remove_popup_image' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ]);

        // Handle popup image upload / removal
        if ($request->hasFile('popup_image')) {
            if ($discountCode->popup_image) {
                Storage::disk('public')->delete($discountCode->popup_image);
            }
            $data['popup_image'] = $request->file('popup_image')->store('discount-codes', 'public');
        } elseif ($request->boolean('remove_popup_image')) {
            if ($discountCode->popup_image) {
                Storage::disk('public')->delete($discountCode->popup_image);
            }
            $data['popup_image'] = null;
        }

        unset($data['remove_popup_image']);

        $discountCode->update($data);

        AuditLogger::log('updated', $discountCode);

        return redirect()->route('admin.discount-codes.index')->with('success', 'Discount code updated successfully.');
    }

    public function destroy(DiscountCode $discountCode): RedirectResponse
    {
        AuditLogger::log('deleted', $discountCode);

        if ($discountCode->popup_image) {
            Storage::disk('public')->delete($discountCode->popup_image);
        }

        $discountCode->delete();

        return redirect()->route('admin.discount-codes.index')->with('success', 'Discount code deleted successfully.');
    }

    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
            'amount' => 'nullable|numeric|min:0',
            'service_id' => 'nullable|exists:services,id',
        ]);

        $discountCode = DiscountCode::where('code', $request->input('code'))
            ->valid()
            ->first();

        if (!$discountCode) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid or expired discount code.',
            ]);
        }

        // Check if code is applicable to specific service
        if ($request->input('service_id') && !empty($discountCode->applicable_services)) {
            if (!in_array($request->input('service_id'), $discountCode->applicable_services)) {
                return response()->json([
                    'valid' => false,
                    'message' => 'This discount code is not applicable to the selected service.',
                ]);
            }
        }

        $amount = $request->input('amount', 0);
        $discount = $discountCode->calculateDiscount($amount);

        return response()->json([
            'valid' => true,
            'discount_code' => $discountCode,
            'discount_amount' => $discount,
            'message' => 'Discount code is valid.',
        ]);
    }
}
