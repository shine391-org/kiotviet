<?php

namespace App\Repositories\Products;

use App\Models\ProductBatchModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

/**
 * Product batch persistence.
 *
 * @agent-repository: Product batches
 * @agent-pattern: Repository pattern
 * @agent-reusable: HIGH
 */
class ProductBatchRepository
{
    protected ProductBatchModel $model;
    protected BaseConnection $db;

    public function __construct(?ProductBatchModel $model = null, ?BaseConnection $db = null)
    {
        $this->model = $model ?? new ProductBatchModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function db(): BaseConnection
    {
        return $this->db;
    }

    public function find(int $id): ?array
    {
        $row = $this->model->find($id);
        return $row ?: null;
    }

    public function findByProductAndNumber(int $productId, string $batchNumber): ?array
    {
        $row = $this->model->where('product_id', $productId)
            ->where('batch_number', $batchNumber)
            ->first();
        return $row ?: null;
    }

    /** List batches with optional filters. */
    public function list(array $filters = []): array
    {
        $b = $this->db->table('product_batches');
        if (! empty($filters['product_id'])) {
            $b->where('product_id', $filters['product_id']);
        }
        if (! empty($filters['variant_id'])) {
            $b->where('variant_id', $filters['variant_id']);
        }
        if (! empty($filters['status'])) {
            $b->where('status', $filters['status']);
        }
        if (! empty($filters['search'])) {
            $b->groupStart()
                ->like('batch_number', $filters['search'])
                ->orLike('reference_document', $filters['search'])
                ->groupEnd();
        }
        if (! empty($filters['expiring_in_days'])) {
            $days = (int) $filters['expiring_in_days'];
            $start = date('Y-m-d');
            $end = date('Y-m-d', strtotime('+' . $days . ' days'));
            $b->where('expiry_date IS NOT NULL', null, false)
              ->where('expiry_date >=', $start)
              ->where('expiry_date <=', $end);
        }
        return $b->orderBy('expiry_date', 'ASC')->orderBy('id', 'DESC')->get()->getResultArray();
    }

    /** Create batch row. */
    public function create(array $data): array
    {
        $now = date('Y-m-d H:i:s');
        $payload = $data + ['created_at' => $now, 'updated_at' => $now];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $payload;
    }

    /** Update batch row. */
    public function update(int $id, array $data): bool
    {
        return (bool) $this->model->update($id, $data + ['updated_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Adjust quantity with pessimistic lock.
     *
     * @throws RuntimeException
     */
    public function adjustQuantity(int $id, float $delta): array
    {
        $this->db->transBegin();
        $row = $this->lockRow($id);
        if (! $row) {
            $this->db->transRollback();
            throw new RuntimeException('Batch not found');
        }
        $newQty = (float) ($row['current_quantity'] ?? 0) + $delta;
        if ($newQty < 0) {
            $this->db->transRollback();
            throw new RuntimeException('Insufficient batch quantity');
        }
        $update = [
            'current_quantity' => $newQty,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->table('product_batches')->where('id', $id)->update($update);
        $this->db->transCommit();
        return array_merge($row, $update);
    }

    private function lockRow(int $id): ?array
    {
        $table = $this->db->prefixTable('product_batches');
        $sql = "SELECT * FROM {$table} WHERE id = ?";
        if (strtolower($this->db->DBDriver) !== 'sqlite3') {
            $sql .= " FOR UPDATE";
        }
        $row = $this->db->query($sql, [$id])->getRowArray();
        return $row ?: null;
    }
}
