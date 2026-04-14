<?php

namespace Database\Seeders;

use App\Models\DiscountCode;
use App\Models\PackageBundle;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class DiscountCodeSeeder extends Seeder
{
    /**
     * Seed discount / promo codes covering every scenario:
     *
     *  1. WELCOME10     – percentage, all services, first booking only, website popup
     *  2. FLAT100       – fixed amount, all services & packages, no conditions
     *  3. LASER20       – percentage, specific services only (laser category)
     *  4. BRIDE500      – fixed, specific package only (Bride Package)
     *  5. VIP30         – percentage with max discount cap, per-patient limit
     *  6. SUMMER25      – percentage, min order amount, date-bounded (active now)
     *  7. EXPIRED2025   – expired code (date in the past)
     *  8. MAXEDOUT      – code that has reached its max uses
     *  9. INACTIVE      – manually deactivated code
     * 10. SKINCARE15    – percentage, specific services (skincare category), unlimited
     */
    public function run(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('name', 'super_admin'))->first();
        $createdBy = $admin?->id;

        // Gather real service IDs by partial name match
        $services = Service::all();
        $findServiceIds = function (array $partials) use ($services): array {
            return $services
                ->filter(fn ($s) => collect($partials)->contains(
                    fn ($p) => str_contains(strtolower($s->name_en ?? ''), strtolower($p))
                ))
                ->pluck('id')
                ->values()
                ->toArray();
        };

        $laserServiceIds    = $findServiceIds(['laser', 'full body laser']);
        $skincareServiceIds = $findServiceIds(['hydrafacial', 'deep skin', 'chemical & cold', 'carbon']);
        $allPackageIds      = PackageBundle::pluck('id')->toArray();
        $bridePackageId     = PackageBundle::where('name_en', 'LIKE', '%Bride%')->value('id');

        // ─────────────────────────────────────────────────────────────────
        // 1. WELCOME10 – First booking only, website popup, percentage
        // ─────────────────────────────────────────────────────────────────
        DiscountCode::updateOrCreate(
            ['code' => 'WELCOME10'],
            [
                'discount_type'        => 'percentage',
                'discount_value'       => 10.00,
                'max_uses'             => 500,
                'used_count'           => 12,
                'start_date'           => now()->subDays(10)->toDateString(),
                'end_date'             => now()->addMonths(3)->toDateString(),
                'is_active'            => true,
                'applicable_services'  => null,  // all services
                'applicable_packages'  => null,  // all packages
                'min_order_amount'     => 0,
                'max_discount_amount'  => null,
                'per_patient_limit'    => null,
                'first_booking_only'   => true,
                'show_on_website'      => true,
                'popup_title_en'       => 'Welcome to Doctorato!',
                'popup_title_ar'       => 'مرحبا بك في اورا ديرما!',
                'popup_description_en' => 'Enjoy 10% off your first booking with us. Use the code below at checkout.',
                'popup_description_ar' => 'استمتعي بخصم 10% على حجزك الاول معنا. استخدمي الكود ادناه عند الحجز.',
                'popup_image'          => null,
                'notes'                => 'Welcome offer for first-time customers. Displayed as website popup.',
                'created_by'           => $createdBy,
            ]
        );

        // ─────────────────────────────────────────────────────────────────
        // 2. FLAT100 – Fixed 100 EGP off, no restrictions
        // ─────────────────────────────────────────────────────────────────
        DiscountCode::updateOrCreate(
            ['code' => 'FLAT100'],
            [
                'discount_type'        => 'fixed',
                'discount_value'       => 100.00,
                'max_uses'             => null,  // unlimited
                'used_count'           => 45,
                'start_date'           => now()->subMonth()->toDateString(),
                'end_date'             => now()->addMonths(6)->toDateString(),
                'is_active'            => true,
                'applicable_services'  => null,
                'applicable_packages'  => null,
                'min_order_amount'     => 0,
                'max_discount_amount'  => null,
                'per_patient_limit'    => null,
                'first_booking_only'   => false,
                'show_on_website'      => false,
                'notes'                => 'General promo - 100 EGP flat discount, unlimited uses.',
                'created_by'           => $createdBy,
            ]
        );

        // ─────────────────────────────────────────────────────────────────
        // 3. LASER20 – 20% off laser services only
        // ─────────────────────────────────────────────────────────────────
        DiscountCode::updateOrCreate(
            ['code' => 'LASER20'],
            [
                'discount_type'        => 'percentage',
                'discount_value'       => 20.00,
                'max_uses'             => 100,
                'used_count'           => 8,
                'start_date'           => now()->subWeek()->toDateString(),
                'end_date'             => now()->addMonths(2)->toDateString(),
                'is_active'            => true,
                'applicable_services'  => $laserServiceIds ?: null,
                'applicable_packages'  => null,
                'min_order_amount'     => 0,
                'max_discount_amount'  => 500.00,
                'per_patient_limit'    => 2,
                'first_booking_only'   => false,
                'show_on_website'      => false,
                'notes'                => 'Laser-category services only, max 500 EGP discount, 2 uses per patient.',
                'created_by'           => $createdBy,
            ]
        );

        // ─────────────────────────────────────────────────────────────────
        // 4. BRIDE500 – Fixed 500 EGP off Bride Package only
        // ─────────────────────────────────────────────────────────────────
        DiscountCode::updateOrCreate(
            ['code' => 'BRIDE500'],
            [
                'discount_type'        => 'fixed',
                'discount_value'       => 500.00,
                'max_uses'             => 50,
                'used_count'           => 3,
                'start_date'           => now()->subDays(5)->toDateString(),
                'end_date'             => now()->addMonths(4)->toDateString(),
                'is_active'            => true,
                'applicable_services'  => null,
                'applicable_packages'  => $bridePackageId ? [$bridePackageId] : null,
                'min_order_amount'     => 0,
                'max_discount_amount'  => null,
                'per_patient_limit'    => 1,
                'first_booking_only'   => false,
                'show_on_website'      => false,
                'notes'                => 'Bride Package exclusive - 500 EGP off, one use per patient.',
                'created_by'           => $createdBy,
            ]
        );

        // ─────────────────────────────────────────────────────────────────
        // 5. VIP30 – 30% off with max cap 300 EGP, per-patient limit 3
        // ─────────────────────────────────────────────────────────────────
        DiscountCode::updateOrCreate(
            ['code' => 'VIP30'],
            [
                'discount_type'        => 'percentage',
                'discount_value'       => 30.00,
                'max_uses'             => 200,
                'used_count'           => 67,
                'start_date'           => now()->subMonth()->toDateString(),
                'end_date'             => now()->addMonths(2)->toDateString(),
                'is_active'            => true,
                'applicable_services'  => null,
                'applicable_packages'  => $allPackageIds ?: null,
                'min_order_amount'     => 500.00,
                'max_discount_amount'  => 300.00,
                'per_patient_limit'    => 3,
                'first_booking_only'   => false,
                'show_on_website'      => false,
                'notes'                => 'VIP clients - 30% off (max 300 EGP), min order 500 EGP, up to 3 uses per patient.',
                'created_by'           => $createdBy,
            ]
        );

        // ─────────────────────────────────────────────────────────────────
        // 6. SUMMER25 – Seasonal, min 300 EGP order, active now
        // ─────────────────────────────────────────────────────────────────
        DiscountCode::updateOrCreate(
            ['code' => 'SUMMER25'],
            [
                'discount_type'        => 'percentage',
                'discount_value'       => 25.00,
                'max_uses'             => 300,
                'used_count'           => 0,
                'start_date'           => now()->toDateString(),
                'end_date'             => now()->addMonths(3)->toDateString(),
                'is_active'            => true,
                'applicable_services'  => null,
                'applicable_packages'  => null,
                'min_order_amount'     => 300.00,
                'max_discount_amount'  => 400.00,
                'per_patient_limit'    => null,
                'first_booking_only'   => false,
                'show_on_website'      => false,
                'notes'                => 'Summer campaign - 25% off, min order 300 EGP, max discount 400 EGP.',
                'created_by'           => $createdBy,
            ]
        );

        // ─────────────────────────────────────────────────────────────────
        // 7. EXPIRED2025 – Code with past end_date (should fail validation)
        // ─────────────────────────────────────────────────────────────────
        DiscountCode::updateOrCreate(
            ['code' => 'EXPIRED2025'],
            [
                'discount_type'        => 'percentage',
                'discount_value'       => 15.00,
                'max_uses'             => 100,
                'used_count'           => 30,
                'start_date'           => '2025-01-01',
                'end_date'             => '2025-12-31',
                'is_active'            => true,  // active flag, but date expired
                'applicable_services'  => null,
                'applicable_packages'  => null,
                'min_order_amount'     => 0,
                'max_discount_amount'  => null,
                'per_patient_limit'    => null,
                'first_booking_only'   => false,
                'show_on_website'      => false,
                'notes'                => 'Expired code from 2025 - should fail date validation.',
                'created_by'           => $createdBy,
            ]
        );

        // ─────────────────────────────────────────────────────────────────
        // 8. MAXEDOUT – Code that reached its max_uses limit
        // ─────────────────────────────────────────────────────────────────
        DiscountCode::updateOrCreate(
            ['code' => 'MAXEDOUT'],
            [
                'discount_type'        => 'fixed',
                'discount_value'       => 50.00,
                'max_uses'             => 10,
                'used_count'           => 10,   // fully used
                'start_date'           => now()->subMonth()->toDateString(),
                'end_date'             => now()->addMonth()->toDateString(),
                'is_active'            => true,
                'applicable_services'  => null,
                'applicable_packages'  => null,
                'min_order_amount'     => 0,
                'max_discount_amount'  => null,
                'per_patient_limit'    => null,
                'first_booking_only'   => false,
                'show_on_website'      => false,
                'notes'                => 'All 10 uses consumed - should fail max_uses validation.',
                'created_by'           => $createdBy,
            ]
        );

        // ─────────────────────────────────────────────────────────────────
        // 9. INACTIVE – Manually deactivated code
        // ─────────────────────────────────────────────────────────────────
        DiscountCode::updateOrCreate(
            ['code' => 'INACTIVE'],
            [
                'discount_type'        => 'percentage',
                'discount_value'       => 50.00,
                'max_uses'             => null,
                'used_count'           => 0,
                'start_date'           => now()->subWeek()->toDateString(),
                'end_date'             => now()->addYear()->toDateString(),
                'is_active'            => false,  // deactivated
                'applicable_services'  => null,
                'applicable_packages'  => null,
                'min_order_amount'     => 0,
                'max_discount_amount'  => null,
                'per_patient_limit'    => null,
                'first_booking_only'   => false,
                'show_on_website'      => false,
                'notes'                => 'Manually deactivated by admin - should fail is_active validation.',
                'created_by'           => $createdBy,
            ]
        );

        // ─────────────────────────────────────────────────────────────────
        // 10. SKINCARE15 – 15% off skincare services, unlimited
        // ─────────────────────────────────────────────────────────────────
        DiscountCode::updateOrCreate(
            ['code' => 'SKINCARE15'],
            [
                'discount_type'        => 'percentage',
                'discount_value'       => 15.00,
                'max_uses'             => null,   // unlimited
                'used_count'           => 22,
                'start_date'           => now()->subDays(14)->toDateString(),
                'end_date'             => now()->addMonths(6)->toDateString(),
                'is_active'            => true,
                'applicable_services'  => $skincareServiceIds ?: null,
                'applicable_packages'  => null,
                'min_order_amount'     => 200.00,
                'max_discount_amount'  => null,
                'per_patient_limit'    => null,
                'first_booking_only'   => false,
                'show_on_website'      => false,
                'notes'                => 'Skincare category only - HydraFacial, Deep Skin, Chemical Peel, Carbon. Min 200 EGP.',
                'created_by'           => $createdBy,
            ]
        );

        $this->command->info('Discount codes seeded: 10 codes covering all scenarios.');
    }
}
