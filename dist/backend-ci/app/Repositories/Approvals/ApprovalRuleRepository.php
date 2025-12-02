<?php

namespace App\Repositories\Approvals;

use App\Models\ApprovalRuleModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Approval rule persistence.
 *
 * @agent-repository: Approval rules
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class ApprovalRuleRepository
{
    protected ApprovalRuleModel $rules;
    protected BaseConnection $db;

    public function __construct(?ApprovalRuleModel $rules = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->ensureTable();
        $this->rules = $rules ?? new ApprovalRuleModel();
    }

    public function create(array $data): array
    {
        $now = date('Y-m-d H:i:s');
        $payload = $data + ['created_at' => $now, 'updated_at' => $now];
        $this->rules->insert($payload);
        $payload['id'] = (int) $this->rules->getInsertID();
        return $payload;
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->rules->update($id, $data + ['updated_at' => date('Y-m-d H:i:s')]);
    }

    public function list(array $filters = []): array
    {
        $b = $this->db->table('order_approval_rules');
        if (isset($filters['is_active'])) {
            $b->where('is_active', $filters['is_active']);
        }
        if (! empty($filters['condition_type'])) {
            $b->where('condition_type', $filters['condition_type']);
        }
        return $b->orderBy('priority', 'ASC')->orderBy('id', 'ASC')->get()->getResultArray();
    }

    public function find(int $id): ?array
    {
        $row = $this->rules->find($id);
        return $row ?: null;
    }

    private function ensureTable(): void
    {
        if ($this->db->tableExists('order_approval_rules') || ENVIRONMENT !== 'testing') {
            return;
        }
        $this->db->query("CREATE TABLE IF NOT EXISTS order_approval_rules (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(150) NOT NULL,
            condition_type VARCHAR(50) DEFAULT 'amount',
            threshold_amount DECIMAL(14,2) DEFAULT 0,
            customer_id BIGINT UNSIGNED NULL,
            custom_condition TEXT NULL,
            approver_ids TEXT NOT NULL,
            priority INT DEFAULT 100,
            is_active TINYINT(1) DEFAULT 1,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            KEY idx_rule_condition (condition_type, customer_id),
            KEY idx_rule_priority (priority)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }
}
