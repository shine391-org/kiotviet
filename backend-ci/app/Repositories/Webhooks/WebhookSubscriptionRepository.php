<?php

namespace App\Repositories\Webhooks;

use App\Models\WebhookSubscriptionModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Webhook subscriptions persistence.
 *
 * @agent-repository: Webhook subscriptions
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class WebhookSubscriptionRepository
{
    protected WebhookSubscriptionModel $model;
    protected BaseConnection $db;

    public function __construct(?WebhookSubscriptionModel $model = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
        $this->model = $model ?? new WebhookSubscriptionModel();
    }

    /** Whether tables exist. */
    public function isReady(): bool
    {
        return $this->db->tableExists('webhook_subscriptions');
    }

    /** List subscriptions with filters + pagination. */
    public function findAll(array $filters): array
    {
        $builder = $this->model->builder();

        if (! empty($filters['event'])) {
            $builder->where('event', $filters['event']);
        }
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $builder->where('is_active', $filters['is_active'] ? 1 : 0);
        }

        $limit = $filters['limit'] ?? 20;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        $rows = $builder
            ->orderBy('event', 'ASC')
            ->orderBy('id', 'DESC')
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
        if (array_key_exists('is_active', $filters) && $filters['is_active'] !== null) {
            $builder->where('is_active', $filters['is_active'] ? 1 : 0);
        }
        return (int) $builder->countAllResults();
    }

    public function findById(int $id): ?array
    {
        $row = $this->model->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    public function create(array $data): array
    {
        $payload = $this->encode($data) + [
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $this->hydrate($payload);
    }

    public function update(int $id, array $data): bool
    {
        $payload = $this->encode($data) + ['updated_at' => $this->now()];
        return (bool) $this->model->update($id, $payload);
    }

    public function activate(int $id): bool
    {
        return $this->update($id, ['is_active' => 1]);
    }

    public function deactivate(int $id): bool
    {
        return $this->update($id, ['is_active' => 0]);
    }

    /** Fetch active subscriptions for event. */
    public function activeForEvent(string $event): array
    {
        if (! $this->isReady()) {
            return [];
        }
        $rows = $this->model->builder()
            ->where('event', $event)
            ->where('is_active', 1)
            ->get()
            ->getResultArray();
        return array_map(fn ($row) => $this->hydrate($row), $rows);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        if (isset($row['is_active'])) {
            $row['is_active'] = (bool) $row['is_active'];
        }
        return $row;
    }

    private function encode(array $data): array
    {
        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = $data['is_active'] ? 1 : 0;
        }
        return $data;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
