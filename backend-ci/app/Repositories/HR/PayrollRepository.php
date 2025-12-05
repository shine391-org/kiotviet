<?php

namespace App\Repositories\HR;

use App\Models\PayrollEntryModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Payroll entry repository.
 *
 * @agent-repository: Payroll
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class PayrollRepository
{
    protected PayrollEntryModel $entries;
    protected BaseConnection $db;

    public function __construct(?PayrollEntryModel $entries = null, ?BaseConnection $db = null)
    {
        $this->entries = $entries ?? new PayrollEntryModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function nextNumber(): string
    {
        $prefix = 'PAY-' . date('Ymd');
        $count = $this->entries->where('payroll_number LIKE', $prefix . '%')->countAllResults();
        return $prefix . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->entries->insert($payload);
        $payload['id'] = (int) $this->entries->getInsertID();
        return $payload;
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->entries->update($id, ['status' => $status, 'updated_at' => $this->now()]);
    }

    public function find(int $id): ?array
    {
        $row = $this->entries->find($id);
        return $row ?: null;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
