<?php

namespace App\Repositories\Ecommerce;

use App\Models\EcommerceWebhookLogModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Ecommerce webhook idempotency log repository.
 *
 * @agent-repository: Ecommerce webhook logs
 * @agent-pattern: Idempotency log
 * @agent-reusable: MEDIUM
 */
class EcommerceWebhookLogRepository
{
    protected EcommerceWebhookLogModel $logs;
    protected BaseConnection $db;

    public function __construct(?EcommerceWebhookLogModel $logs = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->logs = $logs ?? new EcommerceWebhookLogModel($this->db);
    }

    public function findByKey(string $key): ?array
    {
        $row = $this->logs->where('idempotency_key', $key)->first();
        return $row ?: null;
    }

    public function log(string $source, string $event, string $key, string $hash): array
    {
        $payload = [
            'source' => $source,
            'event_type' => $event,
            'idempotency_key' => $key,
            'payload_hash' => $hash,
            'status' => 'processed',
            'processed_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->logs->insert($payload);
        $payload['id'] = $this->logs->getInsertID();
        return $payload;
    }
}
