<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate scheduler rules.
 *
 * @agent-validator: Scheduler
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class SchedulerValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateRule(array $input): array
    {
        $data = $this->run($input, [
            'name' => 'required|string|max_length[150]',
            'cron_expression' => 'required|string|max_length[60]',
            'handler' => 'required|string|max_length[120]',
            'is_active' => 'permit_empty|integer|in_list[0,1]',
        ]);
        $data['is_active'] = isset($data['is_active']) ? (int) $data['is_active'] : 1;
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
