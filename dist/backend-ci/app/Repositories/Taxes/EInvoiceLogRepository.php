<?php

namespace App\Repositories\Taxes;

use App\Models\EInvoiceLogModel;
use CodeIgniter\Database\BaseConnection;

/**
 * E-invoice log repository.
 *
 * @agent-repository: E-invoice log
 * @agent-pattern: Repository pattern
 * @agent-reusable: LOW
 */
class EInvoiceLogRepository
{
    protected EInvoiceLogModel $logs;
    protected BaseConnection $db;

    public function __construct(?EInvoiceLogModel $logs = null, ?BaseConnection $db = null)
    {
        $this->logs = $logs ?? new EInvoiceLogModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function log(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->logs->insert($payload);
        $payload['id'] = (int) $this->logs->getInsertID();
        return $payload;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
