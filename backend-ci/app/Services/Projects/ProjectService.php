<?php

namespace App\Services\Projects;

use App\Repositories\Projects\ProjectRepository;
use App\Repositories\Projects\TaskRepository;
use App\Validators\ProjectValidator;
use RuntimeException;

/**
 * Project business logic.
 *
 * @agent-service: Project
 * @agent-pattern: CRUD + progress
 * @agent-reusable: MEDIUM
 */
class ProjectService
{
    protected ProjectRepository $projects;
    protected TaskRepository $tasks;
    protected ProjectValidator $validator;

    public function __construct(
        ?ProjectRepository $projects = null,
        ?TaskRepository $tasks = null,
        ?ProjectValidator $validator = null
    ) {
        $this->projects = $projects ?? new ProjectRepository();
        $this->tasks = $tasks ?? new TaskRepository();
        $this->validator = $validator ?? new ProjectValidator();
    }

    /** @agent-use: GET /api/projects @agent-pattern: List with filters */
    public function list(array $filters): array
    {
        return ['success' => true, 'data' => $this->projects->list($filters)];
    }

    /** @agent-use: GET /api/projects/{id} @agent-pattern: Get by id */
    public function show(int $id): array
    {
        $project = $this->requireProject($id);
        $project['tasks'] = $this->tasks->listByProject($id);
        return ['success' => true, 'data' => $project];
    }

    /** @agent-use: POST /api/projects @agent-pattern: Standard create */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $project = $this->projects->create($data);
        return ['success' => true, 'data' => $project];
    }

    /** @agent-use: PUT /api/projects/{id} @agent-pattern: Standard update */
    public function update(int $id, array $input): array
    {
        $this->requireProject($id);
        $data = $this->validator->validateUpdate($input);
        $this->projects->update($id, $data);
        return ['success' => true, 'data' => $this->requireProject($id)];
    }

    /**
     * Recalculate project progress from tasks (simple average).
     *
     * @agent-use: Internal after task changes
     * @agent-pattern: Aggregate calculation
     */
    public function recalcProgress(int $projectId): void
    {
        $tasks = $this->tasks->listByProject($projectId);
        if (empty($tasks)) {
            $this->projects->updateProgress($projectId, 0);
            return;
        }
        $progressSum = array_sum(array_map(fn ($t) => (float) ($t['progress'] ?? 0), $tasks));
        $avg = round($progressSum / count($tasks), 2);
        $this->projects->updateProgress($projectId, $avg);
    }

    private function requireProject(int $id): array
    {
        $row = $this->projects->find($id);
        if (! $row) {
            throw new RuntimeException('Project not found');
        }
        return $row;
    }
}
