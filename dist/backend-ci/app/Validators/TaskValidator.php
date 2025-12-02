<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate tasks.
 *
 * @agent-validator: Task
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class TaskValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'project_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'parent_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'task_name' => 'required|string|max_length[255]',
            'status' => 'permit_empty|string|max_length[30]',
            'progress' => 'permit_empty|decimal',
            'estimated_hours' => 'permit_empty|decimal',
            'actual_hours' => 'permit_empty|decimal',
            'start_date' => 'permit_empty|valid_date[Y-m-d]',
            'due_date' => 'permit_empty|valid_date[Y-m-d]',
            'assigned_to' => 'permit_empty|integer|greater_than_equal_to[0]',
            'created_by' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);
        $status = $data['status'] ?? 'open';
        $this->assertStatus($status);
        $data['status'] = $status;
        $data['progress'] = isset($data['progress']) ? (float) $data['progress'] : 0.0;
        $data['estimated_hours'] = isset($data['estimated_hours']) ? (float) $data['estimated_hours'] : 0.0;
        $data['actual_hours'] = isset($data['actual_hours']) ? (float) $data['actual_hours'] : 0.0;
        return $data;
    }

    public function validateStatusUpdate(array $input): array
    {
        $data = $this->run($input, [
            'status' => 'required|string|max_length[30]',
            'progress' => 'permit_empty|decimal',
        ]);
        $this->assertStatus($data['status']);
        $data['progress'] = isset($data['progress']) ? (float) $data['progress'] : null;
        return $data;
    }

    private function assertStatus(string $status): void
    {
        $allowed = ['open', 'in_progress', 'completed', 'cancelled'];
        if (! in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Invalid task status');
        }
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
