<?php

namespace App\Repositories\Products;

use App\Models\ProductSerialNumberModel;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

/**
 * Product serial number persistence.
 *
 * @agent-repository: Product serial numbers
 * @agent-pattern: Repository pattern
 * @agent-reusable: HIGH
 */
class ProductSerialNumberRepository
{
    protected ProductSerialNumberModel $model;
    protected BaseConnection $db;

    public function __construct(?ProductSerialNumberModel $model = null, ?BaseConnection $db = null)
    {
        $this->model = $model ?? new ProductSerialNumberModel();
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

    public function findBySerial(string $serialNumber): ?array
    {
        $row = $this->model->where('serial_number', $serialNumber)->first();
        return $row ?: null;
    }

    public function list(array $filters = []): array
    {
        $b = $this->db->table('product_serial_numbers');
        if (! empty($filters['product_id'])) {
            $b->where('product_id', $filters['product_id']);
        }
        if (! empty($filters['variant_id'])) {
            $b->where('variant_id', $filters['variant_id']);
        }
        if (! empty($filters['batch_id'])) {
            $b->where('batch_id', $filters['batch_id']);
        }
        if (! empty($filters['status'])) {
            $b->where('status', $filters['status']);
        }
        if (! empty($filters['order_id'])) {
            $orderId = (int) $filters['order_id'];
            $b->groupStart()
                ->where('reserved_for_order_id', $orderId)
                ->orWhere('sold_to_order_id', $orderId)
                ->groupEnd();
        }
        if (! empty($filters['search'])) {
            $b->like('serial_number', $filters['search']);
        }
        return $b->orderBy('id', 'DESC')->get()->getResultArray();
    }

    public function create(array $data): array
    {
        $now = date('Y-m-d H:i:s');
        $payload = $data + ['created_at' => $now, 'updated_at' => $now];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $payload;
    }

    /** Reserve serial number for an order. */
    public function reserve(string $serialNumber, int $orderId): array
    {
        return $this->withTransaction(function () use ($serialNumber, $orderId) {
            $row = $this->lockBySerial($serialNumber);
            if (! $row) {
                throw new RuntimeException('Serial number not found');
            }
            if ($row['status'] === 'sold') {
                throw new RuntimeException('Serial already sold');
            }
            if ($row['status'] === 'reserved' && (int) ($row['reserved_for_order_id'] ?? 0) !== $orderId) {
                throw new RuntimeException('Serial already reserved');
            }

            $now = date('Y-m-d H:i:s');
            $update = [
                'status' => 'reserved',
                'reserved_for_order_id' => $orderId,
                'reserved_at' => $now,
                'updated_at' => $now,
            ];
            $this->db->table('product_serial_numbers')->where('id', $row['id'])->update($update);
            return array_merge($row, $update);
        });
    }

    /** Release reservation (back to available). */
    public function release(string $serialNumber): array
    {
        return $this->withTransaction(function () use ($serialNumber) {
            $row = $this->lockBySerial($serialNumber);
            if (! $row) {
                throw new RuntimeException('Serial number not found');
            }
            $now = date('Y-m-d H:i:s');
            $update = [
                'status' => 'available',
                'reserved_for_order_id' => null,
                'reserved_at' => null,
                'updated_at' => $now,
            ];
            $this->db->table('product_serial_numbers')->where('id', $row['id'])->update($update);
            return array_merge($row, $update);
        });
    }

    /** Mark serial as sold. */
    public function markSold(string $serialNumber, int $orderId): array
    {
        return $this->withTransaction(function () use ($serialNumber, $orderId) {
            $row = $this->lockBySerial($serialNumber);
            if (! $row) {
                throw new RuntimeException('Serial number not found');
            }
            if ($row['status'] === 'sold' && (int) ($row['sold_to_order_id'] ?? 0) === $orderId) {
                return $row;
            }
            if (($row['status'] === 'reserved') && $row['reserved_for_order_id'] && (int) $row['reserved_for_order_id'] !== $orderId) {
                throw new RuntimeException('Serial reserved by another order');
            }

            $now = date('Y-m-d H:i:s');
            $update = [
                'status' => 'sold',
                'sold_to_order_id' => $orderId,
                'sold_date' => $now,
                'reserved_for_order_id' => null,
                'reserved_at' => null,
                'updated_at' => $now,
            ];
            $this->db->table('product_serial_numbers')->where('id', $row['id'])->update($update);
            return array_merge($row, $update);
        });
    }

    /** Mark serial as returned. */
    public function markReturned(string $serialNumber, ?int $orderId = null): array
    {
        return $this->withTransaction(function () use ($serialNumber, $orderId) {
            $row = $this->lockBySerial($serialNumber);
            if (! $row) {
                throw new RuntimeException('Serial number not found');
            }

            $now = date('Y-m-d H:i:s');
            $update = [
                'status' => 'returned',
                'returned_at' => $now,
                'reserved_for_order_id' => null,
                'reserved_at' => null,
                'updated_at' => $now,
            ];
            if ($orderId) {
                $update['sold_to_order_id'] = $orderId;
                $update['sold_date'] = $row['sold_date'] ?? $now;
            }
            $this->db->table('product_serial_numbers')->where('id', $row['id'])->update($update);
            return array_merge($row, $update);
        });
    }

    private function lockBySerial(string $serialNumber): ?array
    {
        $table = $this->db->prefixTable('product_serial_numbers');
        $sql = "SELECT * FROM {$table} WHERE serial_number = ?";
        if (strtolower($this->db->DBDriver) !== 'sqlite3') {
            $sql .= " FOR UPDATE";
        }
        $row = $this->db->query($sql, [$serialNumber])->getRowArray();
        return $row ?: null;
    }

    private function withTransaction(callable $callback)
    {
        $started = $this->db->transDepth === 0;
        if ($started) {
            $this->db->transBegin();
        }
        try {
            $result = $callback();
            if ($started) {
                $this->db->transCommit();
            }
            return $result;
        } catch (\Throwable $e) {
            if ($started) {
                $this->db->transRollback();
            }
            throw $e;
        }
    }
}
