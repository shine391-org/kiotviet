<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate maintenance payloads.
 *
 * @agent-validator: Maintenance
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class MaintenanceValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateSchedule(array $input): array
    {
        $data = $this->run($input, [
            'asset_id' => 'required|integer|greater_than[0]',
            'schedule_name' => 'required|string|max_length[150]',
            'frequency' => 'permit_empty|string|max_length[50]',
            'next_due_date' => 'permit_empty|valid_date[Y-m-d]',
        ]);
        $data['frequency'] = $data['frequency'] ?? 'monthly';
        return $data;
    }

    public function validateWorkOrderCreate(array $input): array
    {
        $data = $this->run($input, [
            'asset_id' => 'required|integer|greater_than[0]',
            'schedule_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'description' => 'permit_empty|string',
            'planned_date' => 'permit_empty|valid_date[Y-m-d]',
        ]);
        return $data;
    }

    public function validateComplete(array $input): array
    {
        $data = $this->run($input, [
            'parts' => 'permit_empty',
        ]);
        $data['parts'] = $this->normalizeParts($input['parts'] ?? []);
        return $data;
    }

    private function normalizeParts($parts): array
    {
        if (! is_array($parts)) {
            return [];
        }
        $normalized = [];
        foreach ($parts as $part) {
            $productId = (int) ($part['product_id'] ?? 0);
            $qty = (float) ($part['qty'] ?? 0);
            if ($productId <= 0 || $qty <= 0) {
                throw new InvalidArgumentException('parts product_id and qty required');
            }
            $normalized[] = [
                'product_id' => $productId,
                'qty' => $qty,
                'warehouse_id' => isset($part['warehouse_id']) ? (int) $part['warehouse_id'] : null,
                'branch_id' => isset($part['branch_id']) ? (int) $part['branch_id'] : null,
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
