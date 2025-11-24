<?php

namespace App\Repositories\Inventory;

use App\Models\InventoryMovementModel;
use CodeIgniter\Database\BaseConnection;

/** Inventory movements persistence. @agent-repository: Inventory movements @agent-pattern: Repository pattern @agent-reusable: HIGH */
class InventoryMovementRepository
{
    protected InventoryMovementModel $movements;
    protected BaseConnection $db;

    public function __construct(?InventoryMovementModel $movements = null, ?BaseConnection $db = null)
    {
        $this->movements = $movements ?? new InventoryMovementModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function log(array $data): array
    {
        $now = date('Y-m-d H:i:s');
        $payload = [
            'branch_id' => $data['branch_id'],
            'product_id' => $data['product_id'],
            'variant_id' => $data['variant_id'] ?? null,
            'type' => $data['type'],
            'quantity' => $data['quantity'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $data['created_by'] ?? null,
            'created_at' => $data['created_at'] ?? $now,
            'updated_at' => $data['updated_at'] ?? $now,
        ];
        $this->movements->insert($payload);
        $payload['id'] = $this->movements->getInsertID();
        return $payload;
    }

    public function byReference(string $type, int $id): array
    {
        return $this->movements->where('reference_type', $type)->where('reference_id', $id)->findAll();
    }
}
