<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\OnlineConsultation;
use App\Services\OnlineConsultationService;
use App\Services\Payment\Drivers\PaymobGateway;
use App\Services\Payment\Drivers\StripeGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function paymob(Request $request, PaymobGateway $gateway, OnlineConsultationService $consultationService)
    {
        if (!$gateway->verifyWebhookSignature($request)) {
            Log::warning('Paymob webhook signature mismatch', ['ip' => $request->ip()]);
            return response('Invalid signature', 403);
        }

        $transaction = $gateway->handleWebhook($request);

        if ($transaction && $transaction->status === 'succeeded' && $transaction->online_consultation_id) {
            $consultation = OnlineConsultation::find($transaction->online_consultation_id);
            if ($consultation && $consultation->payment_status !== 'paid') {
                $consultationService->markPaid(
                    $consultation,
                    $transaction->gateway_reference ?: $transaction->transaction_id
                );
            }
        }

        return response('OK', 200);
    }

    public function stripe(Request $request, StripeGateway $gateway, OnlineConsultationService $consultationService)
    {
        if (!$gateway->verifyWebhookSignature($request)) {
            Log::warning('Stripe webhook signature mismatch', ['ip' => $request->ip()]);
            return response('Invalid signature', 403);
        }

        $transaction = $gateway->handleWebhook($request);

        if ($transaction && $transaction->status === 'succeeded' && $transaction->online_consultation_id) {
            $consultation = OnlineConsultation::find($transaction->online_consultation_id);
            if ($consultation && $consultation->payment_status !== 'paid') {
                $consultationService->markPaid(
                    $consultation,
                    $transaction->gateway_reference ?: $transaction->transaction_id
                );
            }
        }

        return response('OK', 200);
    }
}
