<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Patient;
use App\Models\Supply;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->validate([
            'q' => 'required|string|min:2|max:100',
        ])['q'];

        $results = [];

        // Search Patients
        $patients = Patient::where('full_name', 'like', "%{$query}%")
            ->orWhere('phone', 'like', "%{$query}%")
            ->orWhere('file_number', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->select('id', 'full_name', 'phone', 'file_number')
            ->limit(5)
            ->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'type' => 'patient',
                'title' => $p->full_name,
                'subtitle' => $p->file_number ? "#{$p->file_number}" : $p->phone,
                'url' => "/admin/patients/{$p->id}",
            ]);
        $results = array_merge($results, $patients->toArray());

        // Search Invoices
        $invoices = Invoice::where('invoice_number', 'like', "%{$query}%")
            ->orWhereHas('patient', fn ($q) => $q->where('full_name', 'like', "%{$query}%"))
            ->with('patient:id,full_name')
            ->select('id', 'invoice_number', 'patient_id', 'total', 'status')
            ->limit(5)
            ->get()
            ->map(fn ($i) => [
                'id' => $i->id,
                'type' => 'invoice',
                'title' => $i->invoice_number,
                'subtitle' => ($i->patient?->full_name ?? '') . " - {$i->total}",
                'url' => "/admin/invoices/{$i->id}",
            ]);
        $results = array_merge($results, $invoices->toArray());

        // Search Visits (today or recent)
        $visits = Visit::whereHas('patient', fn ($q) => $q->where('full_name', 'like', "%{$query}%")
                ->orWhere('phone', 'like', "%{$query}%"))
            ->with('patient:id,full_name', 'service:id,name_en')
            ->select('id', 'patient_id', 'visit_date', 'status')
            ->latest('visit_date')
            ->limit(5)
            ->get()
            ->map(fn ($v) => [
                'id' => $v->id,
                'type' => 'visit',
                'title' => $v->patient?->full_name ?? '-',
                'subtitle' => "{$v->visit_date} - {$v->status}",
                'url' => "/admin/visits/{$v->id}",
            ]);
        $results = array_merge($results, $visits->toArray());

        // Search Supplies
        $supplies = Supply::where('name_ar', 'like', "%{$query}%")
            ->orWhere('name_en', 'like', "%{$query}%")
            ->orWhere('sku', 'like', "%{$query}%")
            ->select('id', 'name_ar', 'name_en', 'sku', 'quantity')
            ->limit(5)
            ->get()
            ->map(fn ($s) => [
                'id' => $s->id,
                'type' => 'supply',
                'title' => $s->name_en ?: $s->name_ar,
                'subtitle' => $s->sku ? "SKU: {$s->sku}" : "Qty: {$s->quantity}",
                'url' => "/admin/supplies/{$s->id}",
            ]);
        $results = array_merge($results, $supplies->toArray());

        return response()->json([
            'results' => $results,
            'count' => count($results),
        ]);
    }
}
