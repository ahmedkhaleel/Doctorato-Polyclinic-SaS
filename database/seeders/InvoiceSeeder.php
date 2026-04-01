<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $completedVisits = Visit::where('status', 'completed')
            ->with(['service', 'doctor', 'patient'])
            ->get();

        $paymentMethods = PaymentMethod::all();
        $creator = User::first();

        if ($completedVisits->isEmpty()) {
            return;
        }

        foreach ($completedVisits as $visit) {
            $isConsultation = $visit->visit_type === 'consultation';
            $price = $isConsultation
                ? (float) ($visit->doctor?->consultation_fee ?? 300)
                : (float) ($visit->service?->price_after_discount ?? $visit->service?->price ?? 500);

            $invoice = new Invoice([
                'invoice_number' => Invoice::generateInvoiceNumber(),
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'subtotal' => $price,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'total' => $price,
                'created_by' => $creator?->id,
            ]);
            $invoice->paid_amount = 0;
            $invoice->status = 'unpaid';
            $invoice->save();

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'description_en' => $isConsultation ? 'Consultation' : ($visit->service?->name_en ?? 'Service Session'),
                'description_ar' => $isConsultation ? 'كشف' : ($visit->service?->name_ar ?? 'جلسة خدمة'),
                'quantity' => 1,
                'unit_price' => $price,
                'discount' => 0,
                'total' => $price,
            ]);

            // 70% of invoices are paid, 15% partial, 15% unpaid
            $rand = rand(1, 100);
            if ($rand <= 70 && $paymentMethods->isNotEmpty()) {
                // Fully paid
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'patient_id' => $visit->patient_id,
                    'payment_method_id' => $paymentMethods->random()->id,
                    'amount' => $price,
                    'payment_date' => $visit->completed_at?->toDateString() ?? now()->toDateString(),
                    'received_by' => $creator?->id,
                ]);
                $invoice->update(['paid_amount' => $price, 'status' => 'paid']);
            } elseif ($rand <= 85 && $paymentMethods->isNotEmpty()) {
                // Partial payment
                $partialAmount = round($price * rand(30, 70) / 100, 2);
                Payment::create([
                    'invoice_id' => $invoice->id,
                    'patient_id' => $visit->patient_id,
                    'payment_method_id' => $paymentMethods->random()->id,
                    'amount' => $partialAmount,
                    'payment_date' => $visit->completed_at?->toDateString() ?? now()->toDateString(),
                    'received_by' => $creator?->id,
                ]);
                $invoice->update(['paid_amount' => $partialAmount, 'status' => 'partial']);
            }
            // else stays unpaid
        }
    }
}
