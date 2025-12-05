<?php

namespace App\Repositories\Orders;

use App\Models\OrderSubscriptionModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Order subscription persistence.
 *
 * @agent-repository: Order subscriptions
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class OrderSubscriptionRepository
{
    protected OrderSubscriptionModel $subscriptions;
    protected BaseConnection $db;

    public function __construct(?OrderSubscriptionModel $subscriptions = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->subscriptions = $subscriptions ?? new OrderSubscriptionModel($this->db);
    }

    /** Create subscription row. */
    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->subscriptions->insert($payload);
        $payload['id'] = (int) $this->subscriptions->getInsertID();
        return $payload;
    }

    /** Update subscription. */
    public function update(int $id, array $data): array
    {
        $payload = $data + ['updated_at' => $this->now()];
        $this->subscriptions->update($id, $payload);
        return $this->find($id) ?? [];
    }

    /** Fetch subscription. */
    public function find(int $id): ?array
    {
        $row = $this->subscriptions->find($id);
        return $row ? (is_array($row) ? $row : (array) $row) : null;
    }

    /** List due subscriptions. */
    public function due(string $now): array
    {
        return $this->subscriptions->builder()
            ->where('status', 'active')
            ->where('next_run_at <=', $now)
            ->get()
            ->getResultArray();
    }

    /** Basic list with filters. */
    public function list(array $filters = []): array
    {
        $b = $this->subscriptions->builder();
        if (! empty($filters['status'])) {
            $b->where('status', $filters['status']);
        }
        if (! empty($filters['template_id'])) {
            $b->where('template_id', $filters['template_id']);
        }
        return $b->orderBy('next_run_at', 'ASC')->get()->getResultArray();
    }

    public function db(): BaseConnection
    {
        return $this->db;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
