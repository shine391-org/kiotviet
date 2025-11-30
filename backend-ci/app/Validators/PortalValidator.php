<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate portal payloads.
 *
 * @agent-validator: Portal
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class PortalValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateLogin(array $input): array
    {
        return $this->run($input, [
            'email' => 'required|valid_email',
            'password' => 'required|string|min_length[4]',
        ]);
    }

    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'email' => 'required|valid_email',
            'password' => 'required|string|min_length[4]',
            'customer_id' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);
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
