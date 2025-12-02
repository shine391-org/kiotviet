<?php

namespace App\Repositories\Subscriptions;

use App\Models\SubscriptionCycleModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Subscription cycle repository.
 *
 * @agent-repository: Subscription cycles
 * @agent-pattern: Idempotent cycle log
 * @agent-reusable: MEDIUM
 */
class SubscriptionCycleRepository
{
    protected SubscriptionCycleModel $cycles;
    protected BaseConnection $db;

    public function __construct(?SubscriptionCycleModel $cycles = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->cycles = $cycles ?? new SubscriptionCycleModel($this->db);
    }

    public function findCycle(int $subscriptionId, string $runDate): ?array
    {
        $row = $this->cycles->where('subscription_id', $subscriptionId)->where('run_date', $runDate)->first();
        return $row ? (is_array($row) ? $row : (array) $row) : null;
    }

    public function createCycle(int $subscriptionId, string $runDate, ?int $orderId = null): array
    {
        $payload = [
            'subscription_id' => $subscriptionId,
            'run_date' => $runDate,
            'order_id' => $orderId,
            'status' => 'processed',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->cycles->insert($payload);
        $payload['id'] = (int) $this->cycles->getInsertID();
        return $payload;
    }
}
