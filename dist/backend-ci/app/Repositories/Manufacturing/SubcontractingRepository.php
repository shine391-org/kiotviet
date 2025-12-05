<?php

namespace App\Repositories\Manufacturing;

use App\Models\SubcontractingOrderModel;
use App\Models\SubcontractingMaterialModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Subcontracting orders
 * @agent-pattern: Repository with materials
 * @agent-reusable: MEDIUM
 */
class SubcontractingRepository
{
    protected SubcontractingOrderModel $orders;
    protected SubcontractingMaterialModel $materials;
    protected BaseConnection $db;

    public function __construct(
        ?SubcontractingOrderModel $orders = null,
        ?SubcontractingMaterialModel $materials = null,
        ?BaseConnection $db = null
    ) {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->orders = $orders ?? new SubcontractingOrderModel();
        $this->materials = $materials ?? new SubcontractingMaterialModel();
    }

    public function create(array $order, array $materials): array
    {
        $now = $this->now();
        $order['created_at'] = $now;
        $order['updated_at'] = $now;
        $this->db->transStart();
        $this->orders->insert($order);
        $id = (int) $this->orders->getInsertID();
        $rows = [];
        foreach ($materials as $mat) {
            $rows[] = $mat + [
                'subcontracting_order_id' => $id,
                'issued_quantity' => $mat['issued_quantity'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($rows) {
            $this->materials->insertBatch($rows);
        }
        $this->db->transComplete();
        return $this->findById($id);
    }

    public function findById(int $id): ?array
    {
        $order = $this->orders->find($id);
        if (! $order) {
            return null;
        }
        $mats = $this->materials->where('subcontracting_order_id', $id)->findAll();
        $order = $this->hydrate($order);
        $order['materials'] = array_map(fn ($m) => $this->hydrateMat($m), $mats);
        return $order;
    }

    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->orders->update($id, ['status' => $status, 'updated_at' => $this->now()]);
    }

    public function markIssued(array $map): void
    {
        foreach ($map as $id => $qty) {
            $this->materials->update($id, ['issued_quantity' => $qty, 'updated_at' => $this->now()]);
        }
    }

    public function nextNumber(): string
    {
        $prefix = 'SUB-' . date('Ymd');
        $count = $this->orders->where('order_number LIKE', $prefix . '%')->countAllResults();
        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['supplier_id'] = isset($row['supplier_id']) ? (int) $row['supplier_id'] : null;
        $row['product_id'] = isset($row['product_id']) ? (int) $row['product_id'] : null;
        $row['quantity'] = isset($row['quantity']) ? (float) $row['quantity'] : 0.0;
        return $row;
    }

    private function hydrateMat(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['subcontracting_order_id'] = isset($row['subcontracting_order_id']) ? (int) $row['subcontracting_order_id'] : null;
        $row['material_product_id'] = isset($row['material_product_id']) ? (int) $row['material_product_id'] : null;
        $row['quantity'] = isset($row['quantity']) ? (float) $row['quantity'] : 0.0;
        $row['issued_quantity'] = isset($row['issued_quantity']) ? (float) $row['issued_quantity'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
