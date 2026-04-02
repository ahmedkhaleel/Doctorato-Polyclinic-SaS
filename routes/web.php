<?php

use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\ServiceController;
use App\Http\Controllers\Frontend\GalleryController;
use App\Http\Controllers\Frontend\BlogController;
use App\Http\Controllers\Frontend\FaqController;
use App\Http\Controllers\Frontend\BookingController;
use App\Http\Controllers\Frontend\ContactController;
use App\Http\Controllers\Frontend\PackageBundleController;
use App\Http\Controllers\Api\TimeSlotController;
use App\Http\Controllers\Api\PromoCodeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Frontend Web Routes
|--------------------------------------------------------------------------
|
| These routes handle all public-facing pages of the AURA Derma Clinic
| website. All routes are prefixed with a locale segment (ar or en)
| to support bilingual content.
|
*/

// ─── Debug Route (temporary) ──────────────────────────
Route::get('/api/debug-notifications', function () {
    try {
        $checks = [];
        $checks['notifications_table'] = \Illuminate\Support\Facades\Schema::hasTable('notifications');
        $checks['bookings_is_read'] = \Illuminate\Support\Facades\Schema::hasColumn('bookings', 'is_read');
        $checks['contact_messages_is_read'] = \Illuminate\Support\Facades\Schema::hasColumn('contact_messages', 'is_read');
        $checks['php_version'] = PHP_VERSION;
        $checks['laravel_version'] = app()->version();
        $checks['db_driver'] = config('database.default');
        $checks['db_version'] = \DB::select('SELECT VERSION() as v')[0]->v ?? 'unknown';

        if ($checks['notifications_table']) {
            $checks['notifications_count'] = \DB::table('notifications')->count();
            $checks['notifications_columns'] = \Illuminate\Support\Facades\Schema::getColumnListing('notifications');

            // Test JSON_EXTRACT query (same as controller uses)
            try {
                $jsonTest = \DB::table('notifications')
                    ->selectRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type')) as ntype, COUNT(*) as cnt")
                    ->groupByRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.type'))")
                    ->pluck('cnt', 'ntype')
                    ->toArray();
                $checks['json_extract_works'] = true;
                $checks['json_extract_result'] = $jsonTest;
            } catch (\Throwable $e) {
                $checks['json_extract_works'] = false;
                $checks['json_extract_error'] = $e->getMessage();
            }

            // Test sample notification data
            try {
                $sample = \DB::table('notifications')->first();
                if ($sample) {
                    $checks['sample_notification'] = [
                        'id' => $sample->id,
                        'type' => $sample->type,
                        'notifiable_type' => $sample->notifiable_type,
                        'data_type' => gettype($sample->data),
                        'data_preview' => mb_substr((string)$sample->data, 0, 200),
                        'read_at' => $sample->read_at,
                    ];
                }
            } catch (\Throwable $e) {
                $checks['sample_error'] = $e->getMessage();
            }
        }

        if ($checks['bookings_is_read']) {
            $checks['unread_bookings'] = \DB::table('bookings')->where('is_read', false)->count();
        }

        // Check if user auth works
        $checks['auth_user'] = auth()->check() ? auth()->user()->name : 'not logged in';

        // Test Inertia rendering (simulate controller)
        try {
            $checks['inertia_test'] = 'Inertia class exists: ' . class_exists(\Inertia\Inertia::class);
        } catch (\Throwable $e) {
            $checks['inertia_error'] = $e->getMessage();
        }

        // Check vue page file
        $checks['vue_file_exists'] = file_exists(resource_path('js/Pages/Admin/Notifications/Index.vue'));

        // Check latest migration
        $checks['last_migration'] = \DB::table('migrations')->orderBy('id', 'desc')->value('migration');

        // Check User model has Notifiable trait
        try {
            $userModel = new \App\Models\User();
            $checks['user_has_notifiable'] = method_exists($userModel, 'notifications');
        } catch (\Throwable $e) {
            $checks['user_model_error'] = $e->getMessage();
        }

        return response()->json($checks);
    } catch (\Throwable $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'file' => $e->getFile() . ':' . $e->getLine(),
        ], 500);
    }
});

// ─── Public API Routes (Time Slots) ──────────────────
Route::get('/api/time-slots', [TimeSlotController::class, 'available'])->name('api.time-slots')->middleware('throttle:60,1');
Route::get('/api/available-dates', [TimeSlotController::class, 'availableDates'])->name('api.available-dates')->middleware('throttle:60,1');

// ─── Public API Routes (Promo Codes) ─────────────────
Route::get('/api/active-promo', [PromoCodeController::class, 'activePromo'])->name('api.active-promo')->middleware('throttle:30,1');
Route::post('/api/promo-code/validate', [PromoCodeController::class, 'validateCode'])
    ->name('api.promo-code.validate')
    ->middleware('throttle:30,1');

// Redirect root to Arabic home page
Route::redirect('/', '/ar');

// Locale-prefixed routes
Route::prefix('{locale}')
    ->where(['locale' => 'ar|en'])
    ->group(function () {

        // Home
        Route::get('/', [HomeController::class, 'index'])->name('home');

        // About
        Route::get('/about', [PageController::class, 'about'])->name('about');

        // Services
        Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
        Route::get('/services/{slug}', [ServiceController::class, 'show'])->name('services.show');

        // Gallery
        Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery');

        // Blog
        Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
        Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');

        // FAQ
        Route::get('/faq', [FaqController::class, 'index'])->name('faq');

        // Package Bundles
        Route::get('/package-bundles', [PackageBundleController::class, 'index'])->name('package-bundles.index');
        Route::get('/package-bundles/{bundle}', [PackageBundleController::class, 'show'])->name('package-bundles.show');
        Route::get('/package-bundles/{bundle}/book', [PackageBundleController::class, 'book'])->name('package-bundles.book');
        Route::post('/package-bundles/{bundle}/book', [PackageBundleController::class, 'storeBooking'])->name('package-bundles.storeBooking')->middleware('throttle:10,1');

        // Booking
        Route::get('/booking', [BookingController::class, 'create'])->name('booking.create');
        Route::post('/booking', [BookingController::class, 'store'])->name('booking.store')->middleware('throttle:10,1');

        // Contact
        Route::get('/contact', [ContactController::class, 'index'])->name('contact');
        Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:3,1');

        // Static pages (dynamic slug)
        Route::get('/page/{slug}', [PageController::class, 'show'])->name('page.show');
    });

// ─── Patient Satisfaction Survey (public, no auth) ──────────
Route::get('/survey/{token}', [\App\Http\Controllers\Admin\PatientSatisfactionController::class, 'survey'])->name('satisfaction.survey');
Route::post('/survey/{token}', [\App\Http\Controllers\Admin\PatientSatisfactionController::class, 'submitSurvey'])->name('satisfaction.submit')->middleware('throttle:5,1');
Route::get('/survey/{token}/thank-you', function (string $token) {
    return \Inertia\Inertia::render('Satisfaction/ThankYou');
})->name('satisfaction.thank-you');
