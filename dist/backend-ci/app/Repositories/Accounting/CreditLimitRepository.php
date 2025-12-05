<?php

namespace App\Repositories\Accounting;

use App\Models\CreditLimitModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Credit limits
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class CreditLimitRepository
{
    protected CreditLimitModel $limits;
    protected BaseConnection $db;

    public function __construct(?CreditLimitModel $limits = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->limits = $limits ?? new CreditLimitModel();
    }

    public function upsert(int $customerId, float $limitAmount, bool $onHold): array
    {
        $existing = $this->limits->where('customer_id', $customerId)->first();
        $payload = [
            'customer_id' => $customerId,
            'limit_amount' => $limitAmount,
            'on_hold' => $onHold ? 1 : 0,
            'updated_at' => $this->now(),
        ];
        if ($existing) {
            $this->limits->update($existing['id'], $payload);
            $payload['id'] = (int) $existing['id'];
            $payload['created_at'] = $existing['created_at'] ?? $this->now();
        } else {
            $payload['created_at'] = $this->now();
            $this->limits->insert($payload);
            $payload['id'] = (int) $this->limits->getInsertID();
        }
        return $this->hydrate($payload);
    }

    public function findByCustomer(int $customerId): ?array
    {
        $row = $this->limits->where('customer_id', $customerId)->first();
        return $row ? $this->hydrate($row) : null;
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['customer_id'] = isset($row['customer_id']) ? (int) $row['customer_id'] : null;
        $row['limit_amount'] = isset($row['limit_amount']) ? (float) $row['limit_amount'] : 0.0;
        $row['on_hold'] = ! empty($row['on_hold']);
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
