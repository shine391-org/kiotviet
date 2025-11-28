<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate invoice inputs.
 *
 * @agent-validator: Invoices
 * @agent-pattern: Validation first
 * @agent-reusable: HIGH
 */
class InvoiceValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /** Validate list filters. */
    public function validateListFilters(array $input): array
    {
        $data = array_merge(['page' => 1, 'limit' => 20], $input);
        $rules = [
            'page' => 'permit_empty|integer|greater_than_equal_to[1]',
            'limit' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[200]',
            'customer_id' => 'permit_empty|integer|greater_than_equal_to[1]',
            'branch_id' => 'permit_empty|integer|greater_than_equal_to[1]',
            'issue_date_from' => 'permit_empty|valid_date',
            'issue_date_to' => 'permit_empty|valid_date',
            'search' => 'permit_empty|string|max_length[100]',
            'invoice_status' => 'permit_empty|in_list[draft,processing,completed,failed_delivery,cancelled,void]',
            'invoice_type' => 'permit_empty|in_list[standard,return]',
            'e_invoice_status' => 'permit_empty|in_list[pending,processing,issued,rejected,canceled]',
        ];
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid filters');
        }
        $validated = $this->v->getValidated();
        $validated['page'] = (int) ($validated['page'] ?? 1);
        $validated['limit'] = (int) ($validated['limit'] ?? 20);
        $validated['customer_id'] = isset($validated['customer_id']) ? (int) $validated['customer_id'] : null;
        $validated['branch_id'] = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;
        return $validated;
    }

    /** Validate create payload. */
    public function validateCreate(array $input): array
    {
        $rules = [
            'customer_id' => 'required|integer|greater_than_equal_to[1]',
            'branch_id' => 'required|integer|greater_than_equal_to[1]',
            'issue_date' => 'permit_empty|valid_date',
            'due_date' => 'permit_empty|valid_date',
            'vat_rate' => 'permit_empty|decimal|greater_than_equal_to[0]|less_than_equal_to[1]',
            'order_ids' => 'required',
            'notes' => 'permit_empty|string',
            'created_by' => 'permit_empty|integer|greater_than_equal_to[1]',
            'invoice_type' => 'permit_empty|in_list[standard,return]',
        ];
        if (! $this->v->setRules($rules)->run($input)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }

        $issueDate = $input['issue_date'] ?? date('Y-m-d');
        $dueDate = $input['due_date'] ?? null;
        if ($dueDate && $issueDate && $dueDate < $issueDate) {
            throw new InvalidArgumentException('due_date must be after issue_date');
        }

        $vatRate = isset($input['vat_rate']) ? (float) $input['vat_rate'] : 0.1;
        if ($vatRate < 0 || $vatRate > 1) {
            throw new InvalidArgumentException('vat_rate must be between 0 and 1');
        }

        $orderIds = $this->normalizeOrderIds($input['order_ids'] ?? null);
        if (empty($orderIds)) {
            throw new InvalidArgumentException('order_ids is required');
        }

        return [
            'customer_id' => (int) $input['customer_id'],
            'branch_id' => (int) $input['branch_id'],
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'vat_rate' => $vatRate,
            'notes' => isset($input['notes']) ? trim((string) $input['notes']) : null,
            'order_ids' => $orderIds,
            'created_by' => isset($input['created_by']) ? (int) $input['created_by'] : null,
            'invoice_type' => $input['invoice_type'] ?? 'standard',
        ];
    }

    private function normalizeOrderIds($ids): array
    {
        if (! is_array($ids)) {
            throw new InvalidArgumentException('order_ids must be an array');
        }
        $result = [];
        foreach ($ids as $id) {
            $intId = (int) $id;
            if ($intId <= 0) {
                throw new InvalidArgumentException('order_ids must be positive integers');
            }
            $result[] = $intId;
        }
        return array_values(array_unique($result));
    }
}
