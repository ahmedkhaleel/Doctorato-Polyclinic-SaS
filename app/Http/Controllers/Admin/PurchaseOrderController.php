<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\Supply;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = PurchaseOrder::with(['supplier:id,name_ar,name_en,code', 'creator:id,name'])
            ->withCount('items')
            ->when($request->search, fn ($q, $s) => $q->where('po_number', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->supplier_id, fn ($q, $s) => $q->where('supplier_id', $s))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'pending' => PurchaseOrder::pending()->count(),
            'active' => PurchaseOrder::active()->count(),
            'total_value' => (float) PurchaseOrder::active()->sum('total'),
            'overdue' => PurchaseOrder::whereIn('status', ['ordered', 'partially_received'])
                ->whereNotNull('expected_delivery_date')
                ->where('expected_delivery_date', '<', now())
                ->count(),
        ];

        $suppliers = Supplier::active()->select('id', 'name_ar', 'name_en')->get();

        return Inertia::render('Admin/PurchaseOrders/Index', [
            'orders' => $orders,
            'stats' => $stats,
            'suppliers' => $suppliers,
            'filters' => $request->only(['search', 'status', 'supplier_id']),
        ]);
    }

    public function create()
    {
        $suppliers = Supplier::active()->select('id', 'name_ar', 'name_en', 'lead_time_days')->get();
        $supplies = Supply::active()
            ->select('id', 'name_ar', 'name_en', 'sku', 'quantity', 'min_quantity', 'purchase_price', 'unit', 'supplier_id')
            ->get();

        return Inertia::render('Admin/PurchaseOrders/Create', [
            'suppliers' => $suppliers,
            'supplies' => $supplies,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'expected_delivery_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.supply_id' => 'required|exists:supplies,id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $order = DB::transaction(function () use ($data) {
            $po = PurchaseOrder::create([
                'po_number' => PurchaseOrder::generatePoNumber(),
                'supplier_id' => $data['supplier_id'],
                'created_by' => auth()->id(),
                'status' => 'draft',
                'order_date' => now(),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                PurchaseOrderItem::create([
                    'purchase_order_id' => $po->id,
                    'supply_id' => $item['supply_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['quantity_ordered'] * $item['unit_price'],
                ]);
            }

            $po->recalculate();
            return $po;
        });

        AuditLogger::log('created', $order);

        return redirect()->route('admin.purchase-orders.index')
            ->with('success', "PO #{$order->po_number} created");
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        $purchaseOrder->load([
            'supplier',
            'items.supply:id,name_ar,name_en,sku,unit',
            'creator:id,name',
            'approver:id,name',
        ]);

        return Inertia::render('Admin/PurchaseOrders/Show', [
            'order' => $purchaseOrder,
        ]);
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending_approval,approved,ordered,partially_received,received,cancelled',
            'delivery_notes' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $purchaseOrder->status;
        $purchaseOrder->status = $validated['status'];

        if ($validated['status'] === 'approved') {
            $purchaseOrder->approved_by = auth()->id();
        }

        if (in_array($validated['status'], ['received', 'partially_received'])) {
            if ($validated['status'] === 'received') {
                $purchaseOrder->received_date = now();
            }
            $purchaseOrder->delivery_notes = $validated['delivery_notes'] ?? $purchaseOrder->delivery_notes;
        }

        $purchaseOrder->save();

        AuditLogger::log('status_changed', $purchaseOrder, [
            'from' => $oldStatus, 'to' => $validated['status'],
        ]);

        return back()->with('success', 'PO status updated');
    }

    /**
     * Receive items — update quantities and stock levels.
     */
    public function receiveItems(Request $request, PurchaseOrder $purchaseOrder)
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:purchase_order_items,id',
            'items.*.quantity_received' => 'required|numeric|min:0',
            'items.*.batch_number' => 'nullable|string|max:100',
            'items.*.expiry_date' => 'nullable|date',
        ]);

        DB::transaction(function () use ($validated, $purchaseOrder) {
            $receivedValue = 0.0;

            foreach ($validated['items'] as $itemData) {
                $item = PurchaseOrderItem::find($itemData['id']);
                if (!$item || $item->purchase_order_id !== $purchaseOrder->id) continue;

                $newReceived = $itemData['quantity_received'];
                $added = $newReceived - $item->quantity_received;

                $item->update([
                    'quantity_received' => $newReceived,
                    'batch_number' => $itemData['batch_number'] ?? $item->batch_number,
                    'expiry_date' => $itemData['expiry_date'] ?? $item->expiry_date,
                ]);

                // Update supply stock if quantity increased
                if ($added > 0) {
                    $supply = $item->supply;
                    $supply->increment('quantity', $added);

                    // Update batch/expiry if provided
                    if (!empty($itemData['batch_number'])) {
                        $supply->update(['batch_number' => $itemData['batch_number']]);
                    }
                    if (!empty($itemData['expiry_date'])) {
                        $supply->update(['expiry_date' => $itemData['expiry_date']]);
                    }

                    // Audit-trail transaction (field names must match the
                    // supply_transactions schema: transaction_type / unit_cost).
                    $supply->transactions()->create([
                        'transaction_type' => 'purchase',
                        'quantity'         => $added,
                        'unit_cost'        => $item->unit_price,
                        'notes'            => "PO #{$purchaseOrder->po_number}",
                        'created_by'       => auth()->id(),
                    ]);

                    $receivedValue += $added * (float) $item->unit_price;
                }
            }

            // Money flow: record the received stock value as a purchase expense
            // so inventory cost shows in the financial reports. One expense per
            // receive action; partial receives book only their delta value.
            if ($receivedValue > 0) {
                \App\Models\Expense::create([
                    'expense_category_id' => \App\Models\ExpenseCategory::firstOrCreate(
                        ['name_en' => 'Medical Supplies'],
                        ['name_ar' => 'مستلزمات طبية', 'is_active' => true]
                    )->id,
                    'amount'       => round($receivedValue, 2),
                    'expense_date' => now()->toDateString(),
                    'description'  => "Inventory received — PO #{$purchaseOrder->po_number}",
                    'created_by'   => auth()->id(),
                ]);
            }

            // Auto-update PO status
            if ($purchaseOrder->isFullyReceived()) {
                $purchaseOrder->update([
                    'status' => 'received',
                    'received_date' => now(),
                ]);
            } else {
                $purchaseOrder->update(['status' => 'partially_received']);
            }
        });

        AuditLogger::log('items_received', $purchaseOrder);

        return back()->with('success', 'Items received and stock updated');
    }
}
