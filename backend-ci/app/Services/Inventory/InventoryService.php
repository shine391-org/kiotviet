<?php

namespace App\Services\Inventory;

use App\Repositories\Inventory\InventoryRepository;
use App\Validators\InventoryValidator;
use InvalidArgumentException;
use RuntimeException;

/** Inventory business logic. @agent-service: Inventory @agent-pattern: Service orchestrator @agent-reusable: MEDIUM */
class InventoryService
{
    protected InventoryRepository $repo; protected InventoryValidator $validator;
    public function __construct(?InventoryRepository $repo = null, ?InventoryValidator $validator = null)
    { $this->repo = $repo ?? new InventoryRepository(); $this->validator = $validator ?? new InventoryValidator(); }

    /** List warehouses. @agent-use: GET /api/warehouses @agent-pattern: Standard list */
    public function listWarehouses(array $filters): array
    { return ['success' => true, 'data' => $this->repo->warehouses($filters)]; }

    /** Warehouse detail. @agent-use: GET /api/warehouses/{id} @agent-pattern: Get by id */
    public function showWarehouse(int $id): array
    { return ['success' => true, 'data' => $this->requireWarehouse($id)]; }

    /** Create warehouse. @agent-use: POST /api/warehouses @agent-pattern: Standard create */
    public function createWarehouse(array $data): array
    {
        $validated = $this->validator->validateWarehouseCreate($data);
        if (! empty($validated['is_default'])) { $this->clearDefaultWarehouse(); }
        $warehouse = $this->repo->createWarehouse($validated);
        return ['success' => true, 'data' => $warehouse];
    }

    /** Update warehouse. @agent-use: PUT /api/warehouses/{id} @agent-pattern: Standard update */
    public function updateWarehouse(int $id, array $data): array
    {
        $this->requireWarehouse($id);
        $validated = $this->validator->validateWarehouseUpdate($data);
        if (! empty($validated['is_default'])) { $this->clearDefaultWarehouse(); }
        $this->repo->updateWarehouse($id, $validated);
        return ['success' => true];
    }

    /** Delete warehouse (soft). @agent-use: DELETE /api/warehouses/{id} @agent-pattern: Soft delete */
    public function deleteWarehouse(int $id): array
    { $this->requireWarehouse($id); $this->repo->deleteWarehouse($id); return ['success' => true]; }

    /** List movements. @agent-use: GET /api/inventory/movements @agent-pattern: List movements */
    public function movements(array $filters): array
    { return ['success' => true, 'data' => $this->repo->movements($filters)]; }

    /** Create movement and update stock. @agent-use: POST /api/inventory/movements @agent-pattern: Movement handling */
    public function createMovement(array $data): array
    {
        $payload = $this->validator->validateMovement($data);
        $type = $payload['movement_type'];
        $productId = (int) $payload['product_id'];
        $variantId = isset($payload['variant_id']) ? (int) $payload['variant_id'] : null;
        $qty = (float) $payload['quantity'];

        $this->validateWarehousesForMovement($type, $payload);

        $db = $this->repo->db();
        $db->transBegin();
        try {
            $stockChanges = [];
            if ($type === 'IN') {
                $stockChanges[] = [$payload['to_warehouse_id'], $qty];
            } elseif ($type === 'OUT') {
                $stockChanges[] = [$payload['from_warehouse_id'], -$qty];
            } elseif ($type === 'TRANSFER') {
                $stockChanges[] = [$payload['from_warehouse_id'], -$qty];
                $stockChanges[] = [$payload['to_warehouse_id'], $qty];
            } elseif ($type === 'ADJUSTMENT') {
                $stockChanges[] = [$payload['to_warehouse_id'] ?? $payload['from_warehouse_id'], $qty];
            }

            foreach ($stockChanges as [$whId, $delta]) {
                $row = $this->repo->stockRow($productId, $variantId, $whId);
                $newQty = ($row['quantity_on_hand'] ?? 0) + $delta;
                if ($type !== 'IN' && $newQty < 0) {
                    throw new RuntimeException('Insufficient stock');
                }
                $this->repo->adjustStock($productId, $variantId, $whId, $delta);
                $this->maybeCreateLowStockAlert($productId, $variantId, $whId);
            }

            $payload['reference_code'] = $payload['reference_code'] ?? $this->generateRefCode($type);
            $movement = $this->repo->createMovement($payload);

            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }

        return ['success' => true, 'data' => $movement];
    }

    private function validateWarehousesForMovement(string $type, array $payload): void
    {
        if ($type === 'IN' && empty($payload['to_warehouse_id'])) { throw new InvalidArgumentException('to_warehouse_id required'); }
        if ($type === 'OUT' && empty($payload['from_warehouse_id'])) { throw new InvalidArgumentException('from_warehouse_id required'); }
        if ($type === 'TRANSFER' && (empty($payload['from_warehouse_id']) || empty($payload['to_warehouse_id']))) { throw new InvalidArgumentException('from_warehouse_id and to_warehouse_id required'); }
        if ($type === 'ADJUSTMENT' && empty($payload['to_warehouse_id']) && empty($payload['from_warehouse_id'])) { throw new InvalidArgumentException('warehouse required'); }
    }

    private function clearDefaultWarehouse(): void
    { $this->repo->db()->table('warehouses')->update(['is_default' => 0]); }

    private function requireWarehouse(int $id): array
    {
        $wh = $this->repo->warehouseById($id);
        if (! $wh) { throw new RuntimeException('Warehouse not found'); }
        return $wh;
    }

    private function generateRefCode(string $type): string
    { return $type . '-' . date('YmdHis') . '-' . random_int(100, 999); }

    private function maybeCreateLowStockAlert(int $productId, ?int $variantId, int $warehouseId): void
    {
        $row = $this->repo->stockRow($productId, $variantId, $warehouseId);
        if (! $row) { return; }
        $available = ($row['quantity_on_hand'] ?? 0) - ($row['quantity_reserved'] ?? 0);
        if ($row['minimum_stock'] !== null && $available < $row['minimum_stock']) {
            $this->repo->createAlert([
                'alert_type' => $available <= 0 ? 'OUT_OF_STOCK' : 'LOW_STOCK',
                'product_id' => $productId,
                'variant_id' => $variantId,
                'warehouse_id' => $warehouseId,
                'current_quantity' => $available,
                'threshold_quantity' => $row['minimum_stock'],
                'status' => 'active',
            ]);
        }
    }
}
