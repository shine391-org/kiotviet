<?php

namespace App\Repositories\Webhooks;

use App\Models\WebhookEventModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Webhook delivery queue repository.
 *
 * @agent-repository: Webhook events
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class WebhookEventRepository
{
    protected WebhookEventModel $model;
    protected BaseConnection $db;

    public function __construct(?WebhookEventModel $model = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        $this->model = $model ?? new WebhookEventModel($this->db);
    }

    /** Whether table exists. */
    public function isReady(): bool
    {
        return $this->db->tableExists('webhook_events');
    }

    public function create(array $data): array
    {
        $payload = $this->encode($data) + [
            'status' => $data['status'] ?? 'pending',
            'attempts' => $data['attempts'] ?? 0,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $this->hydrate($payload);
    }

    public function findById(int $id): ?array
    {
        $row = $this->model->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    public function findAll(array $filters): array
    {
        $builder = $this->model->builder();
        if (! empty($filters['event'])) {
            $builder->where('event', $filters['event']);
        }
        if (! empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        $limit = $filters['limit'] ?? 50;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;
        $rows = $builder->orderBy('id', 'DESC')
            ->limit($limit, $offset)
            ->get()
            ->getResultArray();
        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    public function count(array $filters): int
    {
        $builder = $this->model->builder();
        if (! empty($filters['event'])) {
            $builder->where('event', $filters['event']);
        }
        if (! empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        return (int) $builder->countAllResults();
    }

    /** Latest pending events for worker/cron. */
    public function pending(int $limit = 50): array
    {
        if (! $this->isReady()) {
            return [];
        }
        $rows = $this->model->builder()
            ->where('status', 'pending')
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();
        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    public function markSent(int $id, int $attempts = 1): bool
    {
        return $this->updateStatus($id, 'sent', null, $attempts);
    }

    public function markFailed(int $id, string $error, int $attempts = 1): bool
    {
        return $this->updateStatus($id, 'failed', $error, $attempts);
    }

    private function updateStatus(int $id, string $status, ?string $error, int $attempts): bool
    {
        return (bool) $this->model->update($id, [
            'status' => $status,
            'last_error' => $error,
            'attempts' => new \CodeIgniter\Database\RawSql('attempts + ' . max(1, $attempts)),
            'updated_at' => $this->now(),
        ]);
    }

    private function hydrate(array $row): array
    {
        if (isset($row['id'])) { $row['id'] = (int) $row['id']; }
        if (isset($row['attempts'])) { $row['attempts'] = (int) $row['attempts']; }
        if (isset($row['payload']) && is_string($row['payload'])) {
            $row['payload'] = json_decode($row['payload'], true) ?: null;
        }
        return $row;
    }

    private function encode(array $data): array
    {
        if (isset($data['payload']) && is_array($data['payload'])) {
            $data['payload'] = json_encode($data['payload']);
        }
        return $data;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
