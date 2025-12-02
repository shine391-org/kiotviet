<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate approval requests and actions.
 *
 * @agent-validator: Approval request
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class ApprovalRequestValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateSubmit(array $input): array
    {
        $data = $this->run($input, [
            'order_id' => 'required|integer|greater_than[0]',
            'requested_by' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);
        return $data;
    }

    public function validateApprove(array $input): array
    {
        $data = $this->run($input, [
            'actor_id' => 'required|integer|greater_than[0]',
            'notes' => 'permit_empty|string',
        ]);
        return $data;
    }

    public function validateReject(array $input): array
    {
        $data = $this->run($input, [
            'actor_id' => 'required|integer|greater_than[0]',
            'notes' => 'permit_empty|string',
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
