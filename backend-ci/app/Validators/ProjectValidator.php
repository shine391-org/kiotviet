<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate projects.
 *
 * @agent-validator: Project
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class ProjectValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'project_name' => 'required|string|max_length[255]',
            'project_code' => 'permit_empty|string|max_length[80]',
            'customer_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'status' => 'permit_empty|string|max_length[30]',
            'progress' => 'permit_empty|decimal',
            'start_date' => 'permit_empty|valid_date[Y-m-d]',
            'end_date' => 'permit_empty|valid_date[Y-m-d]',
            'description' => 'permit_empty|string',
            'created_by' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);
        $status = $data['status'] ?? 'open';
        $this->assertStatus($status);
        $data['status'] = $status;
        $data['progress'] = isset($data['progress']) ? (float) $data['progress'] : 0.0;
        return $data;
    }

    public function validateUpdate(array $input): array
    {
        $data = $this->run($input, [
            'project_name' => 'permit_empty|string|max_length[255]',
            'project_code' => 'permit_empty|string|max_length[80]',
            'customer_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'status' => 'permit_empty|string|max_length[30]',
            'progress' => 'permit_empty|decimal',
            'start_date' => 'permit_empty|valid_date[Y-m-d]',
            'end_date' => 'permit_empty|valid_date[Y-m-d]',
            'description' => 'permit_empty|string',
        ]);
        if (! empty($data['status'])) {
            $this->assertStatus($data['status']);
        }
        if (isset($data['progress'])) {
            $data['progress'] = (float) $data['progress'];
        }
        return $data;
    }

    private function assertStatus(string $status): void
    {
        $allowed = ['open', 'in_progress', 'completed', 'cancelled'];
        if (! in_array($status, $allowed, true)) {
            throw new InvalidArgumentException('Invalid project status');
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
