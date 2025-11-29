<?php

namespace App\Repositories\POS;

use App\Models\POSOfflineQueueModel;
use App\Services\POS\POSIdempotencyService;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: POS offline queue
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class POSOfflineQueueRepository
{
    protected POSOfflineQueueModel $model;
    protected POSIdempotencyService $idempotency;
    protected BaseConnection $db;

    public function __construct(
        ?POSOfflineQueueModel $model = null,
        ?POSIdempotencyService $idempotency = null,
        ?BaseConnection $db = null
    ) {
        $this->model = $model ?? new POSOfflineQueueModel();
        $this->idempotency = $idempotency ?? new POSIdempotencyService();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function upsert(array $data): array
    {
        $key = $this->idempotency->buildKey($data['temp_id'], $data['device_id']);
        $existing = $this->findByKey($data['temp_id'], $data['device_id']);
        $payload = [
            'temp_id' => $data['temp_id'],
            'device_id' => $data['device_id'],
            'idempotency_key' => $key,
            'user_id' => $data['user_id'] ?? null,
            'branch_id' => $data['branch_id'] ?? null,
            'payload' => json_encode($data['payload']),
            'status' => $existing['status'] ?? 'pending',
            'updated_at' => $this->now(),
        ];
        if ($existing) {
            $this->model->update($existing['id'], $payload);
            return $this->findById($existing['id']);
        }

        $payload['created_at'] = $this->now();
        $this->model->insert($payload);
        $id = (int) $this->model->getInsertID();
        return $this->findById($id) ?? ($payload + ['id' => $id]);
    }

    public function findByKey(string $tempId, string $deviceId): ?array
    {
        $row = $this->model->where('temp_id', $tempId)->where('device_id', $deviceId)->first();
        return $row ? $this->hydrate($row) : null;
    }

    public function findById(int $id): ?array
    {
        $row = $this->model->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    public function markSynced(int $id, ?int $orderId): void
    {
        $this->model->update($id, [
            'status' => 'synced',
            'order_id' => $orderId,
            'error_message' => null,
            'synced_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    public function markFailed(int $id, string $message): void
    {
        $this->model->update($id, [
            'status' => 'failed',
            'error_message' => $message,
            'updated_at' => $this->now(),
        ]);
    }

    public function pending(int $limit = 50): array
    {
        $rows = $this->model->builder()
            ->whereIn('status', ['pending', 'failed'])
            ->orderBy('created_at', 'ASC')
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->get()->getResultArray();
        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['user_id'] = isset($row['user_id']) ? (int) $row['user_id'] : null;
        $row['branch_id'] = isset($row['branch_id']) ? (int) $row['branch_id'] : null;
        $row['order_id'] = isset($row['order_id']) ? (int) $row['order_id'] : null;
        if (isset($row['payload']) && is_string($row['payload'])) {
            $decoded = json_decode($row['payload'], true);
            $row['payload'] = is_array($decoded) ? $decoded : $row['payload'];
        }
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
