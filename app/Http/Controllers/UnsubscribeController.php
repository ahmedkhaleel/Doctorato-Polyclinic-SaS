<?php

namespace App\Http\Controllers;

use App\Models\Patient;
use App\Services\Notifications\ConsentService;
use Illuminate\Http\Request;

/**
 * Public one-click unsubscribe from marketing (reached via a signed link in
 * marketing emails). The `signed` middleware validates the URL; no login needed.
 */
class UnsubscribeController extends Controller
{
    public function __invoke(Request $request, int $patient)
    {
        $model = Patient::find($patient);
        if ($model) {
            ConsentService::optOutMarketing($model, 'unsubscribe_link', $request->ip());
        }

        // RFC 8058 one-click POST expects a 2xx with no body needed.
        if ($request->isMethod('post')) {
            return response()->json(['unsubscribed' => true]);
        }

        return response()->view('emails.unsubscribed', [
            'clinicName' => \App\Models\Setting::get('clinic_name_ar', \App\Models\Setting::get('clinic_name_en', 'Doctorato')),
        ]);
    }
}
