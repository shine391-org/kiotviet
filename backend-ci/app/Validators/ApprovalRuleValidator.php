<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate approval rules.
 *
 * @agent-validator: Approval rule
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class ApprovalRuleValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'name' => 'required|string|max_length[150]',
            'condition_type' => 'required|in_list[amount,customer,custom]',
            'threshold_amount' => 'permit_empty|numeric',
            'customer_id' => 'permit_empty|integer|greater_than[0]',
            'custom_condition' => 'permit_empty|string',
            'approver_ids' => 'required',
            'priority' => 'permit_empty|integer',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ]);
        $data['approver_ids'] = $this->normalizeApprovers($input['approver_ids'] ?? []);
        if (empty($data['approver_ids'])) {
            throw new InvalidArgumentException('approver_ids is required');
        }
        $data['priority'] = $data['priority'] ?? 100;
        $data['threshold_amount'] = isset($data['threshold_amount']) ? (float) $data['threshold_amount'] : 0;
        $data['is_active'] = filter_var($data['is_active'] ?? 1, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        return $data;
    }

    public function validateUpdate(array $input): array
    {
        $data = $this->run($input, [
            'name' => 'permit_empty|string|max_length[150]',
            'condition_type' => 'permit_empty|in_list[amount,customer,custom]',
            'threshold_amount' => 'permit_empty|numeric',
            'customer_id' => 'permit_empty|integer|greater_than[0]',
            'custom_condition' => 'permit_empty|string',
            'approver_ids' => 'permit_empty',
            'priority' => 'permit_empty|integer',
            'is_active' => 'permit_empty|in_list[0,1,true,false]',
        ]);
        if (isset($input['approver_ids'])) {
            $data['approver_ids'] = $this->normalizeApprovers($input['approver_ids']);
        }
        if (isset($data['threshold_amount'])) {
            $data['threshold_amount'] = (float) $data['threshold_amount'];
        }
        if (isset($data['is_active'])) {
            $data['is_active'] = filter_var($data['is_active'], FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }
        if (empty($data)) {
            throw new InvalidArgumentException('No data to update');
        }
        return $data;
    }

    private function normalizeApprovers($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                $value = $decoded;
            } else {
                $value = array_map('trim', explode(',', $value));
            }
        }
        if (! is_array($value)) {
            return [];
        }
        $ids = array_map(static fn ($v) => (int) $v, $value);
        $ids = array_filter($ids, static fn ($v) => $v > 0);
        return array_values(array_unique($ids));
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
