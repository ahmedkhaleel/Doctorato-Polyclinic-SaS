<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PromoCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PromoCodeController extends Controller
{
    public function __construct(
        private PromoCodeService $promoCodeService,
    ) {}

    /**
     * Get the active website popup promo code.
     */
    public function activePromo(): JsonResponse
    {
        $promo = $this->promoCodeService->getActiveWebsitePromo();

        if (! $promo) {
            return response()->json(['active' => false]);
        }

        return response()->json([
            'active' => true,
            'code' => $promo->code,
            'discount_type' => $promo->discount_type,
            'discount_value' => $promo->discount_value,
            'popup_title_en' => $promo->popup_title_en,
            'popup_title_ar' => $promo->popup_title_ar,
            'popup_description_en' => $promo->popup_description_en,
            'popup_description_ar' => $promo->popup_description_ar,
            'popup_image' => $promo->popup_image,
            'end_date' => $promo->end_date?->toISOString(),
            'max_discount_amount' => $promo->max_discount_amount,
        ]);
    }

    /**
     * Validate a promo code with context.
     */
    public function validateCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string|max:50',
            'booking_type' => 'required|in:dermatology_consultation,cosmetic_consultation,dental_consultation,dental_service,pediatric_consultation,pediatric_service,obgyn_consultation,obgyn_service,psychiatry_consultation,psychiatry_service,neurology_consultation,neurology_service,physiotherapy_consultation,physiotherapy_session,service,package_bundle',
            'service_id' => 'nullable|integer',
            'package_bundle_id' => 'nullable|integer',
            'amount' => 'nullable|numeric|min:0',
        ]);

        $result = $this->promoCodeService->validate(
            code: $request->input('code'),
            bookingType: $request->input('booking_type', 'service'),
            serviceId: $request->integer('service_id'),
            packageBundleId: $request->integer('package_bundle_id'),
            amount: (float) $request->input('amount', 0),
        );

        // Don't expose the full model to the public API
        unset($result['discount_code']);

        return response()->json($result);
    }
}
