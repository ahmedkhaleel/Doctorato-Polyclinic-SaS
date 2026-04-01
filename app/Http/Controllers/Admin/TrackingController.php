<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrackingController extends Controller
{
    protected array $trackingKeys = [
        'tracking_gtm_id',
        'tracking_ga4_id',
        'tracking_fb_pixel_id',
        'tracking_tiktok_pixel_id',
        'tracking_snapchat_pixel_id',
        'tracking_twitter_pixel_id',
        'tracking_head_scripts',
        'tracking_body_start_scripts',
        'tracking_body_end_scripts',
    ];

    public function index(): Response
    {
        $tracking = [];
        foreach ($this->trackingKeys as $key) {
            $tracking[$key] = Setting::get($key, '');
        }

        return Inertia::render('Admin/Tracking/Index', [
            'tracking' => $tracking,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'tracking_gtm_id' => 'nullable|string|max:50',
            'tracking_ga4_id' => 'nullable|string|max:50',
            'tracking_fb_pixel_id' => 'nullable|string|max:50',
            'tracking_tiktok_pixel_id' => 'nullable|string|max:50',
            'tracking_snapchat_pixel_id' => 'nullable|string|max:50',
            'tracking_twitter_pixel_id' => 'nullable|string|max:50',
            'tracking_head_scripts' => 'nullable|string|max:10000',
            'tracking_body_start_scripts' => 'nullable|string|max:10000',
            'tracking_body_end_scripts' => 'nullable|string|max:10000',
        ]);

        foreach ($validated as $key => $value) {
            Setting::set($key, $value ?? '', 'tracking');
        }

        AuditLogger::log('updated', null, ['tracking_settings' => array_keys($validated)]);

        return redirect()->back()->with('success', 'Tracking settings updated successfully.');
    }
}
