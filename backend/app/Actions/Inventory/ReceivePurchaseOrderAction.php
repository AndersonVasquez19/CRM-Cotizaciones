<?php

namespace App\Actions\Inventory;

use App\Models\PurchaseOrder;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class ReceivePurchaseOrderAction
{
    public function __construct(
        private readonly AdjustStockAction $adjustStock,
    ) {}

    public function execute(PurchaseOrder $po, User $user): PurchaseOrder
    {
        if ($po->status === 'recibida') {
            return $po->load(['supplier', 'warehouse', 'items.catalogItem']);
        }

        return DB::transaction(function () use ($po, $user): PurchaseOrder {
            $po->loadMissing('items');

            foreach ($po->items as $item) {
                $this->adjustStock->execute(
                    warehouseId: $po->warehouse_id,
                    catalogItemId: $item->catalog_item_id,
                    type: 'entrada',
                    quantity: (float) $item->quantity,
                    user: $user,
                    referenceType: 'PurchaseOrder',
                    referenceId: $po->id,
                    notes: "Ingreso por Orden de Compra {$po->po_number}",
                );
            }

            $po->update([
                'status' => 'recibida',
                'received_at' => now(),
            ]);

            AuditService::log('purchase_order.received', $po, $user, [
                'po_number' => $po->po_number,
            ]);

            return $po->fresh(['supplier', 'warehouse', 'items.catalogItem']);
        });
    }
}
