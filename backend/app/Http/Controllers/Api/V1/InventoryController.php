<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Inventory\AdjustStockAction;
use App\Http\Controllers\Controller;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventoryController extends Controller
{
    public function warehouses(): JsonResponse
    {
        return response()->json(['data' => Warehouse::where('is_active', true)->get()]);
    }

    public function stocks(Request $request): JsonResponse
    {
        $query = WarehouseStock::with(['warehouse:id,name', 'catalogItem:id,sku,name,unit,item_type']);

        if ($warehouseId = $request->query('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        return response()->json(['data' => $query->orderBy('id', 'desc')->get()]);
    }

    public function movements(): JsonResponse
    {
        $movements = StockMovement::with(['warehouse:id,name', 'catalogItem:id,sku,name', 'user:id,name'])
            ->latest('id')
            ->limit(50)
            ->get();

        return response()->json(['data' => $movements]);
    }

    public function adjust(Request $request, AdjustStockAction $action): JsonResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'catalog_item_id' => ['required', 'exists:catalog_items,id'],
            'type' => ['required', Rule::in(['entrada', 'salida', 'reserva', 'liberacion'])],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $stock = $action->execute(
            warehouseId: (int) $validated['warehouse_id'],
            catalogItemId: (int) $validated['catalog_item_id'],
            type: $validated['type'],
            quantity: (float) $validated['quantity'],
            user: $request->user(),
            notes: $validated['notes'] ?? 'Ajuste manual de inventario',
        );

        return response()->json(['data' => $stock], 201);
    }
}
