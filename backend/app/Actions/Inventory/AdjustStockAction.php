<?php

namespace App\Actions\Inventory;

use App\Models\StockMovement;
use App\Models\User;
use App\Models\WarehouseStock;
use Illuminate\Support\Facades\DB;

class AdjustStockAction
{
    public function execute(
        int $warehouseId,
        int $catalogItemId,
        string $type,
        float $quantity,
        ?User $user = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
    ): WarehouseStock {
        return DB::transaction(function () use (
            $warehouseId,
            $catalogItemId,
            $type,
            $quantity,
            $user,
            $referenceType,
            $referenceId,
            $notes
        ): WarehouseStock {
            $stock = WarehouseStock::firstOrCreate(
                ['warehouse_id' => $warehouseId, 'catalog_item_id' => $catalogItemId],
                ['quantity' => 0, 'reserved_quantity' => 0, 'min_stock' => 2],
            );

            match ($type) {
                'entrada' => $stock->quantity = round((float) $stock->quantity + $quantity, 2),
                'salida' => $stock->quantity = max(0, round((float) $stock->quantity - $quantity, 2)),
                'reserva' => $stock->reserved_quantity = round((float) $stock->reserved_quantity + $quantity, 2),
                'liberacion' => $stock->reserved_quantity = max(0, round((float) $stock->reserved_quantity - $quantity, 2)),
                default => null,
            };

            $stock->save();

            StockMovement::create([
                'warehouse_id' => $warehouseId,
                'catalog_item_id' => $catalogItemId,
                'user_id' => $user?->id,
                'type' => $type,
                'quantity' => $quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'notes' => $notes,
                'created_at' => now(),
            ]);

            return $stock->fresh(['warehouse', 'catalogItem']);
        });
    }
}
