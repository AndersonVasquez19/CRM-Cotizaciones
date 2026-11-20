<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Inventory\ReceivePurchaseOrderAction;
use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(): JsonResponse
    {
        $orders = PurchaseOrder::with(['supplier:id,business_name', 'warehouse:id,name', 'items.catalogItem:id,sku,name'])
            ->latest('id')
            ->get();

        return response()->json(['data' => $orders]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'expected_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.catalog_item_id' => ['required', 'exists:catalog_items,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ]);

        $po = DB::transaction(function () use ($validated, $request): PurchaseOrder {
            $year = now()->format('Y');
            $prefix = "OC-{$year}-";
            $last = PurchaseOrder::where('po_number', 'like', "{$prefix}%")->orderByDesc('po_number')->value('po_number');
            $seq = $last ? ((int) explode('-', $last)[2]) + 1 : 1;

            $total = 0;
            $lines = [];
            foreach ($validated['items'] as $it) {
                $lineTotal = round((float) $it['quantity'] * (float) $it['unit_cost'], 2);
                $total += $lineTotal;
                $lines[] = [
                    'catalog_item_id' => $it['catalog_item_id'],
                    'quantity' => $it['quantity'],
                    'unit_cost' => $it['unit_cost'],
                    'line_total' => $lineTotal,
                ];
            }

            $po = PurchaseOrder::create([
                'po_number' => sprintf('%s%04d', $prefix, $seq),
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'created_by' => $request->user()->id,
                'status' => 'emitida',
                'expected_date' => $validated['expected_date'] ?? now()->addDays(5)->toDateString(),
                'total' => round($total, 2),
                'notes' => $validated['notes'] ?? null,
            ]);

            $po->items()->createMany($lines);

            return $po->load(['supplier', 'warehouse', 'items.catalogItem']);
        });

        return response()->json(['data' => $po], 201);
    }

    public function receive(Request $request, PurchaseOrder $purchaseOrder, ReceivePurchaseOrderAction $action): JsonResponse
    {
        $po = $action->execute($purchaseOrder, $request->user());

        return response()->json(['data' => $po]);
    }
}
