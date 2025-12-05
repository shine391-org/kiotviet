<?php

namespace App\Repositories\Subscriptions;

use App\Models\SubscriptionModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Subscription persistence.
 *
 * @agent-repository: Subscriptions
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class SubscriptionRepository
{
    protected SubscriptionModel $subs;
    protected BaseConnection $db;

    public function __construct(?SubscriptionModel $subs = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->subs = $subs ?? new SubscriptionModel($this->db);
    }

    public function list(array $filters = []): array
    {
        return $this->applyFilters($filters)->orderBy('created_at', 'DESC')->get()->getResultArray();
    }

    public function count(array $filters = []): int
    {
        return $this->applyFilters($filters)->countAllResults();
    }

    public function find(int $id): ?array
    {
        $row = $this->subs->find($id);
        return $row ? (is_array($row) ? $row : (array) $row) : null;
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->subs->insert($payload);
        $payload['id'] = (int) $this->subs->getInsertID();
        return $payload;
    }

    public function update(int $id, array $data): array
    {
        $payload = $data + ['updated_at' => $this->now()];
        $this->subs->update($id, $payload);
        return $this->find($id) ?? [];
    }

    public function due(string $now): array
    {
        return $this->subs->builder()
            ->where('status', 'active')
            ->where('next_run_at <=', $now)
            ->get()
            ->getResultArray();
    }

    private function applyFilters(array $filters)
    {
        $b = $this->subs->builder();
        if (! empty($filters['status'])) { $b->where('status', $filters['status']); }
        if (! empty($filters['customer_id'])) { $b->where('customer_id', $filters['customer_id']); }
        return $b;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
