<?php

namespace App\Repositories\Inventory;

use App\Models\StockBinModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

/**
 * Stock bin repository with locking.
 *
 * @agent-repository: Stock bins
 * @agent-pattern: Repository pattern + locking
 * @agent-reusable: MEDIUM
 */
class StockBinRepository
{
    protected StockBinModel $bins;
    protected BaseConnection $db;

    public function __construct(?StockBinModel $bins = null, ?BaseConnection $db = null)
    {
        $this->bins = $bins ?? new StockBinModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function db(): BaseConnection
    {
        return $this->db;
    }

    public function find(int $productId, ?int $variantId, int $branchId, ?int $batchId): ?array
    {
        $row = $this->bins->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->where('branch_id', $branchId)
            ->where('batch_id', $batchId)
            ->first();
        return $row ?: null;
    }

    /** Lock bin row. Creates one if missing. */
    public function lockBin(int $productId, ?int $variantId, int $branchId, ?int $batchId): array
    {
        $table = $this->db->prefixTable('stock_bins');
        $sql = "SELECT * FROM {$table} WHERE product_id = ? AND branch_id = ? AND ";
        $params = [$productId, $branchId];
        if ($variantId === null) {
            $sql .= "variant_id IS NULL";
        } else {
            $sql .= "variant_id = ?";
            $params[] = $variantId;
        }
        $sql .= " AND ";
        if ($batchId === null) {
            $sql .= "batch_id IS NULL";
        } else {
            $sql .= "batch_id = ?";
            $params[] = $batchId;
        }
        if (strtolower($this->db->DBDriver) !== 'sqlite3') {
            $sql .= " FOR UPDATE";
        }
        $row = $this->db->query($sql, $params)->getRowArray();
        if ($row) {
            return $row;
        }
        $payload = [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'branch_id' => $branchId,
            'batch_id' => $batchId,
            'on_hand_qty' => 0,
            'reserved_qty' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->bins->insert($payload);
        $payload['id'] = (int) $this->bins->getInsertID();
        return $payload;
    }

    /** Adjust quantities atomically. */
    public function adjust(int $productId, ?int $variantId, int $branchId, ?int $batchId, float $deltaOnHand, float $deltaReserved = 0): array
    {
        $this->db->transStart();
        $row = $this->lockBin($productId, $variantId, $branchId, $batchId);
        $newOnHand = (float) ($row['on_hand_qty'] ?? 0) + $deltaOnHand;
        $newReserved = (float) ($row['reserved_qty'] ?? 0) + $deltaReserved;
        if ($newOnHand < 0) {
            $this->db->transRollback();
            throw new RuntimeException('Insufficient stock in bin');
        }
        $this->db->table('stock_bins')->where('id', $row['id'])->update([
            'on_hand_qty' => $newOnHand,
            'reserved_qty' => $newReserved,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $this->db->transComplete();
        return [
            'id' => $row['id'],
            'product_id' => $productId,
            'variant_id' => $variantId,
            'branch_id' => $branchId,
            'batch_id' => $batchId,
            'on_hand_qty' => $newOnHand,
            'reserved_qty' => $newReserved,
        ];
    }
}
