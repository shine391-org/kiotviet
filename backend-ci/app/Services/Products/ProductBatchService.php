<?php

namespace App\Services\Products;

use App\Repositories\Inventory\InventoryRepository;
use App\Repositories\Products\ProductBatchRepository;
use App\Services\Inventory\InventoryMovementLogger;
use App\Validators\ProductBatchValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Product batch business logic.
 *
 * @agent-service: Product batches
 * @agent-pattern: Service orchestrator
 * @agent-reusable: HIGH
 */
class ProductBatchService
{
    protected ProductBatchRepository $repo;
    protected ProductBatchValidator $validator;
    protected InventoryRepository $inventoryRepo;
    protected InventoryMovementLogger $movementLogger;

    public function __construct(
        ?ProductBatchRepository $repo = null,
        ?ProductBatchValidator $validator = null,
        ?InventoryRepository $inventoryRepo = null,
        ?InventoryMovementLogger $movementLogger = null
    ) {
        $this->repo = $repo ?? new ProductBatchRepository();
        $this->validator = $validator ?? new ProductBatchValidator();
        $this->inventoryRepo = $inventoryRepo ?? new InventoryRepository();
        $this->movementLogger = $movementLogger ?? new InventoryMovementLogger();
    }

    /** List batches. @agent-use: GET /api/product-batches */
    public function list(array $filters): array
    {
        $validated = $this->validator->validateListFilters($filters);
        return ['success' => true, 'data' => $this->repo->list($validated)];
    }

    /** List expiring batches. @agent-use: GET /api/product-batches/expiring */
    public function expiring(array $filters): array
    {
        if (isset($filters['days'])) {
            $filters['expiring_in_days'] = $filters['days'];
        }
        $validated = $this->validator->validateListFilters($filters);
        return ['success' => true, 'data' => $this->repo->list($validated)];
    }

    /** Batch detail. */
    public function show(int $id): array
    {
        return ['success' => true, 'data' => $this->requireBatch($id)];
    }

    /** Create batch. */
    public function create(array $data): array
    {
        $payload = $this->validator->validateCreate($data);
        if ($this->repo->findByProductAndNumber($payload['product_id'], $payload['batch_number'])) {
            throw new InvalidArgumentException('Batch number already exists for this product');
        }

        $batch = $this->repo->create($payload);

        if (abs((float) $batch['current_quantity']) > 0) {
            $this->syncInventory($batch, (float) $batch['current_quantity']);
            $this->logMovement($batch, (float) $batch['current_quantity'], [
                'type' => 'batch_init',
                'reference_type' => 'batch',
                'reference_id' => $batch['id'],
                'notes' => 'Initial quantity',
            ]);
        }

        return ['success' => true, 'data' => $batch];
    }

    /** Update batch metadata. */
    public function update(int $id, array $data): array
    {
        $existing = $this->requireBatch($id);
        $payload = $this->validator->validateUpdate($data);
        if (isset($payload['batch_number']) && $payload['batch_number'] !== $existing['batch_number']) {
            if ($this->repo->findByProductAndNumber((int) $existing['product_id'], $payload['batch_number'])) {
                throw new InvalidArgumentException('Batch number already exists for this product');
            }
        }
        $this->repo->update($id, $payload);
        return ['success' => true, 'data' => $this->requireBatch($id)];
    }

    /** Adjust quantity and log movement. */
    public function adjustQuantity(int $id, array $data): array
    {
        $batch = $this->requireBatch($id);
        $payload = $this->validator->validateAdjust($data);
        $delta = (float) $payload['quantity_delta'];
        $serialNumber = $payload['serial_number'] ?? null;

        $updated = $this->repo->adjustQuantity($id, $delta);
        $this->syncInventory($batch, $delta, $payload);
        $this->logMovement($batch, $delta, [
            'type' => $payload['movement_type'] ?? 'batch_adjust',
            'reference_type' => $payload['reference_type'] ?? 'batch_adjust',
            'reference_id' => $payload['reference_id'] ?? $batch['id'],
            'notes' => $payload['reason'] ?? null,
            'serial_number' => $serialNumber,
        ]);

        return ['success' => true, 'data' => $updated];
    }

    private function requireBatch(int $id): array
    {
        $batch = $this->repo->find($id);
        if (! $batch) {
            throw new RuntimeException('Batch not found');
        }
        return $batch;
    }

    private function syncInventory(array $batch, float $delta, array $override = []): void
    {
        $branchId = isset($override['branch_id']) ? (int) $override['branch_id'] : ($batch['branch_id'] ?? null);
        $warehouseId = isset($override['warehouse_id']) ? (int) $override['warehouse_id'] : ($batch['warehouse_id'] ?? ($branchId ?? 0));
        $productId = (int) $batch['product_id'];
        $variantId = isset($batch['variant_id']) ? (int) $batch['variant_id'] : null;

        $this->inventoryRepo->adjustStockWithLock($productId, $variantId, (int) $warehouseId, $delta, $branchId);
    }

    private function logMovement(array $batch, float $quantity, array $meta): void
    {
        $branchId = (int) ($batch['branch_id'] ?? 0);
        if ($branchId <= 0) {
            return;
        }
        $this->movementLogger->log(
            branchId: $branchId,
            productId: (int) $batch['product_id'],
            variantId: isset($batch['variant_id']) ? (int) $batch['variant_id'] : null,
            type: $meta['type'] ?? 'batch_adjust',
            quantity: $quantity,
            batchId: (int) $batch['id'],
            serialNumber: $meta['serial_number'] ?? null,
            referenceType: $meta['reference_type'] ?? 'batch',
            referenceId: $meta['reference_id'] ?? null,
            notes: $meta['notes'] ?? null,
            createdBy: null
        );
    }
}
