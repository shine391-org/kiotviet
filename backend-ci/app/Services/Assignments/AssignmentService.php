<?php

namespace App\Services\Assignments;

use App\Repositories\Assignments\AssignmentRepository;
use App\Validators\AssignmentValidator;
use RuntimeException;

/**
 * Assignment service.
 *
 * @agent-service: Assignment
 * @agent-pattern: Round robin assignment
 * @agent-reusable: MEDIUM
 */
class AssignmentService
{
    protected AssignmentRepository $repo;
    protected AssignmentValidator $validator;

    public function __construct(?AssignmentRepository $repo = null, ?AssignmentValidator $validator = null)
    {
        $this->repo = $repo ?? new AssignmentRepository();
        $this->validator = $validator ?? new AssignmentValidator();
    }

    /** @agent-use: POST /api/assignment-rules */
    public function createRule(array $input): array
    {
        $data = $this->validator->validateRule($input);
        $rule = $this->repo->createRule([
            'name' => $data['name'],
            'entity_type' => $data['entity_type'],
            'strategy' => $data['strategy'],
            'team_members' => json_encode($data['team_members']),
            'is_active' => 1,
        ]);
        return ['success' => true, 'data' => $rule];
    }

    /** @agent-use: POST /api/assignment-rules/assign */
    public function assign(array $input): array
    {
        $data = $this->validator->validateAssign($input);
        $rule = $this->repo->findActiveRule($data['entity_type']);
        if (! $rule) {
            throw new RuntimeException('No active assignment rule');
        }
        $members = json_decode($rule['team_members'] ?? '[]', true) ?: [];
        if (empty($members)) {
            throw new RuntimeException('No members configured');
        }
        $next = $this->nextAssignee($members, (int) ($rule['last_assigned_id'] ?? 0));
        $this->repo->updateLastAssigned((int) $rule['id'], $next);
        $this->repo->log((int) $rule['id'], $data['entity_type'], (int) $data['entity_id'], $next);
        return ['success' => true, 'data' => ['assignee_id' => $next]];
    }

    /** @agent-use: GET /api/assignment-rules */
    public function list(array $filters = []): array
    {
        return ['success' => true, 'data' => $this->repo->listRules($filters)];
    }

    private function nextAssignee(array $members, int $last): int
    {
        $index = array_search($last, $members, true);
        if ($index === false || $index === count($members) - 1) {
            return (int) $members[0];
        }
        return (int) $members[$index + 1];
    }
}
