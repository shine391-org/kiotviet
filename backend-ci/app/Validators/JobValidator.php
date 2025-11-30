<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate job payloads.
 *
 * @agent-validator: Job
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class JobValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateEnqueue(array $input): array
    {
        $data = $this->run($input, [
            'name' => 'required|string|max_length[150]',
            'payload' => 'permit_empty',
            'next_run_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'max_attempts' => 'permit_empty|integer|greater_than_equal_to[1]',
        ]);
        $data['next_run_at'] = $data['next_run_at'] ?? date('Y-m-d H:i:s');
        $data['max_attempts'] = isset($data['max_attempts']) ? (int) $data['max_attempts'] : 3;
        return $data;
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
