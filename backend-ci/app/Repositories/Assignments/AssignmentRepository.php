<?php

namespace App\Repositories\Assignments;

use App\Models\AssignmentRuleModel;
use App\Models\AssignmentLogModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Assignment repository.
 *
 * @agent-repository: Assignment
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class AssignmentRepository
{
    protected AssignmentRuleModel $rules;
    protected AssignmentLogModel $logs;
    protected BaseConnection $db;

    public function __construct(?AssignmentRuleModel $rules = null, ?AssignmentLogModel $logs = null, ?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->ensureTables();
        $this->rules = $rules ?? new AssignmentRuleModel();
        $this->logs = $logs ?? new AssignmentLogModel();
    }

    public function createRule(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->rules->insert($payload);
        $payload['id'] = (int) $this->rules->getInsertID();
        return $payload;
    }

    public function listRules(array $filters = []): array
    {
        $b = $this->rules->builder();
        if (! empty($filters['entity_type'])) {
            $b->where('entity_type', $filters['entity_type']);
        }
        if (isset($filters['is_active'])) {
            $b->where('is_active', $filters['is_active']);
        }
        return $b->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();
    }

    public function findActiveRule(string $entityType): ?array
    {
        $row = $this->rules->where('entity_type', $entityType)->where('is_active', 1)->first();
        return $row ?: null;
    }

    public function updateLastAssigned(int $ruleId, int $assigneeId): void
    {
        $this->rules->update($ruleId, ['last_assigned_id' => $assigneeId, 'updated_at' => $this->now()]);
    }

    public function log(int $ruleId, string $entityType, int $entityId, int $assigneeId): void
    {
        $this->logs->insert([
            'assignment_rule_id' => $ruleId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'assignee_id' => $assigneeId,
            'created_at' => $this->now(),
        ]);
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    private function ensureTables(): void
    {
        if (ENVIRONMENT !== 'testing') {
            return;
        }
        if (! $this->db->tableExists('assignment_rules')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS assignment_rules (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                entity_type VARCHAR(80) NOT NULL,
                strategy VARCHAR(50) DEFAULT 'round_robin',
                team_members JSON NOT NULL,
                last_assigned_id BIGINT UNSIGNED NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at DATETIME NULL,
                updated_at DATETIME NULL,
                KEY idx_assignment_entity (entity_type, is_active)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
        if (! $this->db->tableExists('assignment_logs')) {
            $this->db->query("CREATE TABLE IF NOT EXISTS assignment_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                assignment_rule_id BIGINT UNSIGNED NOT NULL,
                entity_type VARCHAR(80) NOT NULL,
                entity_id BIGINT UNSIGNED NOT NULL,
                assignee_id BIGINT UNSIGNED NOT NULL,
                created_at DATETIME NULL,
                KEY idx_assignment_log_rule (assignment_rule_id),
                KEY idx_assignment_log_entity (entity_type, entity_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        }
    }
}
