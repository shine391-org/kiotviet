<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate timesheets.
 *
 * @agent-validator: Timesheet
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class TimesheetValidator
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
            'employee_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'notes' => 'permit_empty|string',
            'created_by' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);
        $data['items'] = $this->normalizeItems($input['items'] ?? []);
        if (empty($data['items'])) {
            throw new InvalidArgumentException('items is required');
        }
        $data['status'] = 'draft';
        return $data;
    }

    public function validateSubmit(array $input): array
    {
        return $this->run($input, [
            'submitted_by' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);
    }

    private function normalizeItems($items): array
    {
        if (! is_array($items)) {
            throw new InvalidArgumentException('items must be an array');
        }
        $normalized = [];
        foreach ($items as $item) {
            $projectId = isset($item['project_id']) ? (int) $item['project_id'] : null;
            $taskId = isset($item['task_id']) ? (int) $item['task_id'] : null;
            $activityId = isset($item['activity_type_id']) ? (int) $item['activity_type_id'] : null;
            $hours = isset($item['hours']) ? (float) $item['hours'] : 0.0;
            if ($hours <= 0) {
                throw new InvalidArgumentException('hours must be > 0');
            }
            $normalized[] = [
                'project_id' => $projectId,
                'task_id' => $taskId,
                'activity_type_id' => $activityId,
                'work_date' => $item['work_date'] ?? null,
                'hours' => $hours,
                'billing_rate' => isset($item['billing_rate']) ? (float) $item['billing_rate'] : null,
                'cost_rate' => isset($item['cost_rate']) ? (float) $item['cost_rate'] : null,
                'description' => $item['description'] ?? null,
            ];
        }
        return $normalized;
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
