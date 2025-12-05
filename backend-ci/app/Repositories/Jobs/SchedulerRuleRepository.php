<?php

namespace App\Repositories\Jobs;

use App\Models\SchedulerRuleModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Scheduler rule repository.
 *
 * @agent-repository: Scheduler rule
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class SchedulerRuleRepository
{
    protected SchedulerRuleModel $rules;
    protected BaseConnection $db;

    public function __construct(?SchedulerRuleModel $rules = null, ?BaseConnection $db = null)
    {
        $this->rules = $rules ?? new SchedulerRuleModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->rules->insert($payload);
        $payload['id'] = (int) $this->rules->getInsertID();
        return $payload;
    }

    public function list(array $filters = []): array
    {
        $b = $this->rules->builder();
        if (isset($filters['is_active'])) {
            $b->where('is_active', $filters['is_active']);
        }
        return $b->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();
    }

    public function dueRules(): array
    {
        return $this->rules->where('is_active', 1)
            ->where('next_run_at <=', date('Y-m-d H:i:s'))
            ->findAll();
    }

    public function updateSchedule(int $id, ?string $lastRun, ?string $nextRun): void
    {
        $this->rules->update($id, [
            'last_run_at' => $lastRun,
            'next_run_at' => $nextRun,
            'updated_at' => $this->now(),
        ]);
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
