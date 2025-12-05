<?php

namespace App\Services\Inventory;

use App\Repositories\Inventory\InventoryMovementRepository;

/**
 * Thin logger wrapper for inventory movements.
 *
 * @agent-service: Inventory movement logger
 * @agent-pattern: Logging service
 * @agent-reusable: HIGH
 */
class InventoryMovementLogger
{
    protected InventoryMovementRepository $repo;

    public function __construct(?InventoryMovementRepository $repo = null)
    {
        $this->repo = $repo ?? new InventoryMovementRepository();
    }

    public function log(
        int $branchId,
        int $productId,
        ?int $variantId,
        string $type,
        float $quantity,
        ?int $batchId = null,
        ?string $serialNumber = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $notes = null,
        ?int $createdBy = null
    ): array {
        return $this->repo->log([
            'branch_id' => $branchId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'batch_id' => $batchId,
            'serial_number' => $serialNumber,
            'type' => $type,
            'quantity' => $quantity,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'notes' => $notes,
            'created_by' => $createdBy,
        ]);
    }
}
