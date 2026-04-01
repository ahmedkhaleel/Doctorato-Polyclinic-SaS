<?php

use App\Http\Controllers\Webmaster\AuthController;
use App\Http\Controllers\Webmaster\DashboardController;
use App\Http\Controllers\Webmaster\DoctorController;
use App\Http\Controllers\Webmaster\FaqController;
use App\Http\Controllers\Webmaster\GalleryController;
use App\Http\Controllers\Webmaster\PageController;
use App\Http\Controllers\Webmaster\PostCategoryController;
use App\Http\Controllers\Webmaster\PostController;
use App\Http\Controllers\Webmaster\SeoPageController;
use App\Http\Controllers\Webmaster\ServiceCategoryController;
use App\Http\Controllers\Webmaster\ServiceController;
use App\Http\Controllers\Webmaster\SliderController;
use App\Http\Controllers\Webmaster\TagController;
use App\Http\Controllers\Webmaster\TestimonialController;
use App\Http\Controllers\Webmaster\TrackingController;
use App\Http\Controllers\Webmaster\ChatController as WebmasterChatController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Webmaster Routes
|--------------------------------------------------------------------------
|
| These routes handle website content management for the Webmaster panel.
| All routes are prefixed with 'webmaster'. The webmaster can manage
| website content (services, blog, gallery, SEO, etc.) but has no access
| to clinic, finance, HR, or system features.
|
*/

// Webmaster authentication routes (no auth middleware)
Route::get('/login', [AuthController::class, 'showLogin'])->name('webmaster.login');
Route::post('/login', [AuthController::class, 'login'])->name('webmaster.authenticate')->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('webmaster.logout');

// Protected webmaster routes (requires authentication + webmaster role)
Route::middleware('webmaster.auth')->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('webmaster.dashboard');

    // ─── Hero Slider ──────────────────────────────────────────
    Route::get('/slider', [SliderController::class, 'index'])->name('webmaster.slider.index')->middleware('permission:settings.view');
    Route::get('/slider/create', [SliderController::class, 'create'])->name('webmaster.slider.create')->middleware('permission:settings.update');
    Route::post('/slider', [SliderController::class, 'store'])->name('webmaster.slider.store')->middleware('permission:settings.update');
    Route::get('/slider/{heroSlide}/edit', [SliderController::class, 'edit'])->name('webmaster.slider.edit')->middleware('permission:settings.update');
    Route::put('/slider/{heroSlide}', [SliderController::class, 'update'])->name('webmaster.slider.update')->middleware('permission:settings.update');
    Route::delete('/slider/{heroSlide}', [SliderController::class, 'destroy'])->name('webmaster.slider.destroy')->middleware('permission:settings.update');
    Route::post('/slider/update-order', [SliderController::class, 'updateOrder'])->name('webmaster.slider.updateOrder')->middleware('permission:settings.update');

    // ─── Services ──────────────────────────────────────────────
    Route::get('/services', [ServiceController::class, 'index'])->name('webmaster.services.index')->middleware('permission:services.view');
    Route::get('/services/create', [ServiceController::class, 'create'])->name('webmaster.services.create')->middleware('permission:services.create');
    Route::post('/services', [ServiceController::class, 'store'])->name('webmaster.services.store')->middleware('permission:services.create');
    Route::get('/services/{service}/edit', [ServiceController::class, 'edit'])->name('webmaster.services.edit')->middleware('permission:services.update');
    Route::put('/services/{service}', [ServiceController::class, 'update'])->name('webmaster.services.update')->middleware('permission:services.update');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('webmaster.services.destroy')->middleware('permission:services.delete');

    // ─── Service Categories ────────────────────────────────────
    Route::get('/service-categories', [ServiceCategoryController::class, 'index'])->name('webmaster.service-categories.index')->middleware('permission:service_categories.view');
    Route::get('/service-categories/create', [ServiceCategoryController::class, 'create'])->name('webmaster.service-categories.create')->middleware('permission:service_categories.create');
    Route::post('/service-categories', [ServiceCategoryController::class, 'store'])->name('webmaster.service-categories.store')->middleware('permission:service_categories.create');
    Route::get('/service-categories/{service_category}/edit', [ServiceCategoryController::class, 'edit'])->name('webmaster.service-categories.edit')->middleware('permission:service_categories.update');
    Route::put('/service-categories/{service_category}', [ServiceCategoryController::class, 'update'])->name('webmaster.service-categories.update')->middleware('permission:service_categories.update');
    Route::delete('/service-categories/{service_category}', [ServiceCategoryController::class, 'destroy'])->name('webmaster.service-categories.destroy')->middleware('permission:service_categories.delete');

    // ─── Doctors ───────────────────────────────────────────────
    Route::get('/doctors', [DoctorController::class, 'index'])->name('webmaster.doctors.index')->middleware('permission:doctors.view');
    Route::get('/doctors/create', [DoctorController::class, 'create'])->name('webmaster.doctors.create')->middleware('permission:doctors.create');
    Route::post('/doctors', [DoctorController::class, 'store'])->name('webmaster.doctors.store')->middleware('permission:doctors.create');
    Route::get('/doctors/{doctor}', [DoctorController::class, 'show'])->name('webmaster.doctors.show')->middleware('permission:doctors.view');
    Route::get('/doctors/{doctor}/edit', [DoctorController::class, 'edit'])->name('webmaster.doctors.edit')->middleware('permission:doctors.update');
    Route::put('/doctors/{doctor}', [DoctorController::class, 'update'])->name('webmaster.doctors.update')->middleware('permission:doctors.update');
    Route::delete('/doctors/{doctor}', [DoctorController::class, 'destroy'])->name('webmaster.doctors.destroy')->middleware('permission:doctors.delete');

    // ─── Gallery ───────────────────────────────────────────────
    Route::get('/gallery', [GalleryController::class, 'index'])->name('webmaster.gallery.index')->middleware('permission:gallery.view');
    Route::get('/gallery/create', [GalleryController::class, 'create'])->name('webmaster.gallery.create')->middleware('permission:gallery.create');
    Route::post('/gallery', [GalleryController::class, 'store'])->name('webmaster.gallery.store')->middleware('permission:gallery.create');
    Route::get('/gallery/{gallery}/edit', [GalleryController::class, 'edit'])->name('webmaster.gallery.edit')->middleware('permission:gallery.update');
    Route::put('/gallery/{gallery}', [GalleryController::class, 'update'])->name('webmaster.gallery.update')->middleware('permission:gallery.update');
    Route::delete('/gallery/{gallery}', [GalleryController::class, 'destroy'])->name('webmaster.gallery.destroy')->middleware('permission:gallery.delete');

    // ─── Testimonials ──────────────────────────────────────────
    Route::get('/testimonials', [TestimonialController::class, 'index'])->name('webmaster.testimonials.index')->middleware('permission:testimonials.view');
    Route::get('/testimonials/create', [TestimonialController::class, 'create'])->name('webmaster.testimonials.create')->middleware('permission:testimonials.create');
    Route::post('/testimonials', [TestimonialController::class, 'store'])->name('webmaster.testimonials.store')->middleware('permission:testimonials.create');
    Route::get('/testimonials/{testimonial}/edit', [TestimonialController::class, 'edit'])->name('webmaster.testimonials.edit')->middleware('permission:testimonials.update');
    Route::put('/testimonials/{testimonial}', [TestimonialController::class, 'update'])->name('webmaster.testimonials.update')->middleware('permission:testimonials.update');
    Route::delete('/testimonials/{testimonial}', [TestimonialController::class, 'destroy'])->name('webmaster.testimonials.destroy')->middleware('permission:testimonials.delete');

    // ─── FAQs ──────────────────────────────────────────────────
    Route::get('/faqs', [FaqController::class, 'index'])->name('webmaster.faqs.index')->middleware('permission:faqs.view');
    Route::get('/faqs/create', [FaqController::class, 'create'])->name('webmaster.faqs.create')->middleware('permission:faqs.create');
    Route::post('/faqs', [FaqController::class, 'store'])->name('webmaster.faqs.store')->middleware('permission:faqs.create');
    Route::get('/faqs/{faq}/edit', [FaqController::class, 'edit'])->name('webmaster.faqs.edit')->middleware('permission:faqs.update');
    Route::put('/faqs/{faq}', [FaqController::class, 'update'])->name('webmaster.faqs.update')->middleware('permission:faqs.update');
    Route::delete('/faqs/{faq}', [FaqController::class, 'destroy'])->name('webmaster.faqs.destroy')->middleware('permission:faqs.delete');

    // ─── Pages (view + update only) ────────────────────────────
    Route::get('/pages', [PageController::class, 'index'])->name('webmaster.pages.index')->middleware('permission:pages.view');
    Route::get('/pages/{page}/edit', [PageController::class, 'edit'])->name('webmaster.pages.edit')->middleware('permission:pages.update');
    Route::put('/pages/{page}', [PageController::class, 'update'])->name('webmaster.pages.update')->middleware('permission:pages.update');

    // ─── Blog Posts ────────────────────────────────────────────
    Route::get('/posts', [PostController::class, 'index'])->name('webmaster.posts.index')->middleware('permission:posts.view');
    Route::get('/posts/create', [PostController::class, 'create'])->name('webmaster.posts.create')->middleware('permission:posts.create');
    Route::post('/posts', [PostController::class, 'store'])->name('webmaster.posts.store')->middleware('permission:posts.create');
    Route::get('/posts/{post}/edit', [PostController::class, 'edit'])->name('webmaster.posts.edit')->middleware('permission:posts.update');
    Route::put('/posts/{post}', [PostController::class, 'update'])->name('webmaster.posts.update')->middleware('permission:posts.update');
    Route::delete('/posts/{post}', [PostController::class, 'destroy'])->name('webmaster.posts.destroy')->middleware('permission:posts.delete');

    // ─── Post Categories ───────────────────────────────────────
    Route::get('/post-categories', [PostCategoryController::class, 'index'])->name('webmaster.post-categories.index')->middleware('permission:post_categories.view');
    Route::get('/post-categories/create', [PostCategoryController::class, 'create'])->name('webmaster.post-categories.create')->middleware('permission:post_categories.create');
    Route::post('/post-categories', [PostCategoryController::class, 'store'])->name('webmaster.post-categories.store')->middleware('permission:post_categories.create');
    Route::get('/post-categories/{post_category}/edit', [PostCategoryController::class, 'edit'])->name('webmaster.post-categories.edit')->middleware('permission:post_categories.update');
    Route::put('/post-categories/{post_category}', [PostCategoryController::class, 'update'])->name('webmaster.post-categories.update')->middleware('permission:post_categories.update');
    Route::delete('/post-categories/{post_category}', [PostCategoryController::class, 'destroy'])->name('webmaster.post-categories.destroy')->middleware('permission:post_categories.delete');

    // ─── Tags ──────────────────────────────────────────────────
    Route::get('/tags', [TagController::class, 'index'])->name('webmaster.tags.index')->middleware('permission:tags.view');
    Route::get('/tags/create', [TagController::class, 'create'])->name('webmaster.tags.create')->middleware('permission:tags.create');
    Route::post('/tags', [TagController::class, 'store'])->name('webmaster.tags.store')->middleware('permission:tags.create');
    Route::get('/tags/{tag}/edit', [TagController::class, 'edit'])->name('webmaster.tags.edit')->middleware('permission:tags.update');
    Route::put('/tags/{tag}', [TagController::class, 'update'])->name('webmaster.tags.update')->middleware('permission:tags.update');
    Route::delete('/tags/{tag}', [TagController::class, 'destroy'])->name('webmaster.tags.destroy')->middleware('permission:tags.delete');

    // ─── SEO Pages ─────────────────────────────────────────────
    Route::get('/seo-pages', [SeoPageController::class, 'index'])->name('webmaster.seoPages.index')->middleware('permission:settings.view');
    Route::get('/seo-pages/{seoPage}/edit', [SeoPageController::class, 'edit'])->name('webmaster.seoPages.edit')->middleware('permission:settings.view');
    Route::put('/seo-pages/{seoPage}', [SeoPageController::class, 'update'])->name('webmaster.seoPages.update')->middleware('permission:settings.update');

    // ─── Tracking & Pixels ─────────────────────────────────────
    Route::get('/tracking', [TrackingController::class, 'index'])->name('webmaster.tracking.index')->middleware('permission:settings.view');
    Route::post('/tracking', [TrackingController::class, 'update'])->name('webmaster.tracking.update')->middleware('permission:settings.update');

    // ─── Chat / Messaging ─────────────────────────────────────
    Route::get('/chat', [WebmasterChatController::class, 'index'])->name('webmaster.chat.index');
    Route::get('/chat/unread-count', [WebmasterChatController::class, 'unreadCount'])->name('webmaster.chat.unreadCount')->middleware('throttle:30,1');
    Route::get('/chat/{user}', [WebmasterChatController::class, 'show'])->name('webmaster.chat.show');
    Route::post('/chat/{user}', [WebmasterChatController::class, 'store'])->name('webmaster.chat.store')->middleware('throttle:30,1');
    Route::get('/chat/{user}/poll', [WebmasterChatController::class, 'poll'])->name('webmaster.chat.poll')->middleware('throttle:30,1');
    Route::post('/chat/{user}/mark-read', [WebmasterChatController::class, 'markRead'])->name('webmaster.chat.markRead')->middleware('throttle:30,1');
    Route::get('/chat/{user}/older', [WebmasterChatController::class, 'loadOlder'])->name('webmaster.chat.loadOlder')->middleware('throttle:30,1');
});
