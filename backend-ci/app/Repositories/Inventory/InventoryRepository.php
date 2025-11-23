<?php

namespace App\Repositories\Inventory;

use CodeIgniter\Database\BaseConnection;

/** Inventory DB operations. @agent-repository: Inventory persistence @agent-pattern: Repository pattern @agent-reusable: MEDIUM */
class InventoryRepository
{
    protected BaseConnection $db;
    public function __construct(?BaseConnection $db = null) { $this->db = $db ?? \Config\Database::connect(); }

    public function db(): BaseConnection
    {
        return $this->db;
    }

    /** Warehouses list. @agent-use: Warehouse listing */
    public function warehouses(array $filters = []): array
    {
        $b = $this->db->table('warehouses')->where('deleted_at', null);
        if (! empty($filters['status'])) { $b->where('status', $filters['status']); }
        if (! empty($filters['search'])) { $b->like('name', $filters['search'])->orLike('code', $filters['search']); }
        return $b->orderBy('is_default', 'DESC')->orderBy('name', 'ASC')->get()->getResultArray();
    }

    public function warehouseById(int $id): ?array
    {
        $row = $this->db->table('warehouses')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();
        return $row ?: null;
    }

    public function createWarehouse(array $data): array
    {
        $payload = $data + ['created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
        $this->db->table('warehouses')->insert($payload);
        $payload['id'] = $this->db->insertID();
        return $payload;
    }

    public function updateWarehouse(int $id, array $data): bool
    {
        return (bool) $this->db->table('warehouses')->where('id', $id)->update($data + ['updated_at' => date('Y-m-d H:i:s')]);
    }

    public function deleteWarehouse(int $id): bool
    {
        return (bool) $this->db->table('warehouses')->where('id', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
    }

    /** Get stock row (or null). */
    public function stockRow(int $productId, ?int $variantId, int $warehouseId): ?array
    {
        $row = $this->db->table('inventory_stock')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('variant_id', $variantId)
            ->get()->getRowArray();
        return $row ?: null;
    }

    /** Upsert stock quantities. Positive delta increases on-hand. */
    public function adjustStock(int $productId, ?int $variantId, int $warehouseId, float $deltaQty): array
    {
        $now = date('Y-m-d H:i:s');
        $this->db->transStart();

        $row = $this->stockRow($productId, $variantId, $warehouseId);
        if ($row) {
            // Use atomic SQL expression to avoid race condition
            $this->db->table('inventory_stock')->where('id', $row['id'])->set('quantity_on_hand', 'quantity_on_hand + ' . $this->db->escape($deltaQty), false)->set([
                'last_movement_at' => $now,
                'updated_at' => $now,
            ])->update();
            // Re-fetch to get the updated value
            $row = $this->db->table('inventory_stock')->where('id', $row['id'])->get()->getRowArray();

            $this->db->transComplete();
            return $row;
        }
        $payload = [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'warehouse_id' => $warehouseId,
            'quantity_on_hand' => $deltaQty,
            'quantity_reserved' => 0,
            'last_movement_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ];
        $this->db->table('inventory_stock')->insert($payload);
        $payload['id'] = $this->db->insertID();
        $this->db->transComplete();
        return $payload;
    }

    /** Create movement record. */
    public function createMovement(array $data): array
    {
        $payload = $data + ['created_at' => date('Y-m-d H:i:s')];
        $this->db->table('inventory_movements')->insert($payload);
        $payload['id'] = $this->db->insertID();
        return $payload;
    }

    /** List movements (simple filters). */
    public function movements(array $filters = []): array
    {
        $b = $this->db->table('inventory_movements');
        if (! empty($filters['product_id'])) { $b->where('product_id', $filters['product_id']); }
        if (! empty($filters['warehouse_id'])) { $b->groupStart()->where('from_warehouse_id', $filters['warehouse_id'])->orWhere('to_warehouse_id', $filters['warehouse_id'])->groupEnd(); }
        if (! empty($filters['movement_type'])) { $b->where('movement_type', $filters['movement_type']); }
        return $b->orderBy('created_at', 'DESC')->limit(200)->get()->getResultArray();
    }

    /** Create alert row. */
    public function createAlert(array $data): void
    {
        $payload = $data + ['created_at' => date('Y-m-d H:i:s')];
        $this->db->table('inventory_alerts')->insert($payload);
    }

    /** Find alert by id. */
    public function alertById(int $id): ?array
    {
        $row = $this->db->table('inventory_alerts')->where('id', $id)->get()->getRowArray();
        return $row ?: null;
    }

    /** Update alert status. */
    public function updateAlertStatus(int $id, string $status, ?int $userId = null): bool
    {
        $this->db->table('inventory_alerts')->where('id', $id)->update([
            'status' => $status,
            'resolved_by' => $userId,
            'resolved_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->affectedRows() > 0;
    }

    /** List alerts with filters. */
    public function alerts(array $filters = []): array
    {
        $b = $this->db->table('inventory_alerts');
        if (! empty($filters['status'])) { $b->where('status', $filters['status']); }
        if (! empty($filters['warehouse_id'])) { $b->where('warehouse_id', $filters['warehouse_id']); }
        if (! empty($filters['product_id'])) { $b->where('product_id', $filters['product_id']); }
        return $b->orderBy('created_at', 'DESC')->limit(200)->get()->getResultArray();
    }

    /** Reserve stock (increase quantity_reserved). */
    public function reserveStock(int $productId, ?int $variantId, int $warehouseId, float $qty): array
    {
        $this->db->transStart();

        // Use raw SQL with FOR UPDATE to lock the row
        $sql = "SELECT * FROM inventory_stock WHERE product_id = ? AND warehouse_id = ? AND variant_id = ? FOR UPDATE";
        $row = $this->db->query($sql, [$productId, $warehouseId, $variantId])->getRowArray();

        if (! $row) { throw new \RuntimeException('Stock not found'); }
        $newReserved = ($row['quantity_reserved'] ?? 0) + $qty;
        $available = ($row['quantity_on_hand'] ?? 0) - $newReserved;
        if ($available < 0) { throw new \RuntimeException('Insufficient available stock'); }
        $this->db->table('inventory_stock')->where('id', $row['id'])->update([
            'quantity_reserved' => $newReserved,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $row['quantity_reserved'] = $newReserved;
        $this->db->transComplete();
        return $row;
    }

    /** Release reserved stock. */
    public function releaseStock(int $productId, ?int $variantId, int $warehouseId, float $qty): array
    {
        $this->db->transStart();

        // Use raw SQL with FOR UPDATE to lock the row
        $sql = "SELECT * FROM inventory_stock WHERE product_id = ? AND warehouse_id = ? AND variant_id = ? FOR UPDATE";
        $row = $this->db->query($sql, [$productId, $warehouseId, $variantId])->getRowArray();

        if (! $row) { throw new \RuntimeException('Stock not found'); }
        $newReserved = max(0, ($row['quantity_reserved'] ?? 0) - $qty);
        $this->db->table('inventory_stock')->where('id', $row['id'])->update([
            'quantity_reserved' => $newReserved,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $row['quantity_reserved'] = $newReserved;
        $this->db->transComplete();
        return $row;
    }

    /** Create valuation record. @agent-use: valuation tracking */
    public function createValuation(array $data): array
    {
        $payload = $data + ['created_at' => date('Y-m-d H:i:s')];
        $this->db->table('inventory_valuation')->insert($payload);
        $payload['id'] = $this->db->insertID();
        return $payload;
    }

    /** List valuation rows by product/warehouse. */
    public function valuations(array $filters = []): array
    {
        $b = $this->db->table('inventory_valuation');
        if (! empty($filters['product_id'])) { $b->where('product_id', $filters['product_id']); }
        if (! empty($filters['warehouse_id'])) { $b->where('warehouse_id', $filters['warehouse_id']); }
        if (! empty($filters['valuation_method'])) { $b->where('valuation_method', $filters['valuation_method']); }
        return $b->orderBy('created_at', 'DESC')->limit(200)->get()->getResultArray();
    }
}
