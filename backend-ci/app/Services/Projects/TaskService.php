<?php

namespace App\Services\Projects;

use App\Repositories\Projects\TaskRepository;
use App\Validators\TaskValidator;
use RuntimeException;

/**
 * Task business logic.
 *
 * @agent-service: Task
 * @agent-pattern: CRUD + status
 * @agent-reusable: MEDIUM
 */
class TaskService
{
    protected TaskRepository $tasks;
    protected TaskValidator $validator;
    protected ProjectService $projects;

    public function __construct(
        ?TaskRepository $tasks = null,
        ?TaskValidator $validator = null,
        ?ProjectService $projects = null
    ) {
        $this->tasks = $tasks ?? new TaskRepository();
        $this->validator = $validator ?? new TaskValidator();
        $this->projects = $projects ?? new ProjectService();
    }

    /** @agent-use: POST /api/tasks @agent-pattern: Standard create */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $task = $this->tasks->create($data);
        if (! empty($task['project_id'])) {
            $this->projects->recalcProgress((int) $task['project_id']);
        }
        return ['success' => true, 'data' => $task];
    }

    /** @agent-use: PUT /api/tasks/{id}/status @agent-pattern: Status transition */
    public function updateStatus(int $id, array $input): array
    {
        $task = $this->requireTask($id);
        $data = $this->validator->validateStatusUpdate($input);
        $progress = $data['progress'] ?? ($data['status'] === 'completed' ? 100.0 : $task['progress']);
        $this->tasks->updateStatus($id, $data['status'], $progress);
        if (! empty($task['project_id'])) {
            $this->projects->recalcProgress((int) $task['project_id']);
        }
        return ['success' => true, 'data' => $this->requireTask($id)];
    }

    /** @agent-use: GET /api/tasks/{id} @agent-pattern: Get by id */
    public function show(int $id): array
    {
        return ['success' => true, 'data' => $this->requireTask($id)];
    }

    private function requireTask(int $id): array
    {
        $row = $this->tasks->find($id);
        if (! $row) {
            throw new RuntimeException('Task not found');
        }
        return $row;
    }
}
