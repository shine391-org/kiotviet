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
        // Handle array fields separately before validation
        $invoiceStatus = $input['invoice_status'] ?? null;
        $invoiceTypes = $input['invoice_types'] ?? null;
        unset($input['invoice_status'], $input['invoice_types']);

        $data = array_merge(['page' => 1, 'limit' => 20], $input);
        $rules = [
            'page' => 'permit_empty|integer|greater_than_equal_to[1]',
            'limit' => 'permit_empty|integer|greater_than_equal_to[1]|less_than_equal_to[200]',
            'customer_id' => 'permit_empty|integer|greater_than_equal_to[1]',
            'branch_id' => 'permit_empty|integer|greater_than_equal_to[1]',
            'issue_date_from' => 'permit_empty|valid_date',
            'issue_date_to' => 'permit_empty|valid_date',
            'date_from' => 'permit_empty|valid_date',
            'date_to' => 'permit_empty|valid_date',
            'search' => 'permit_empty|string|max_length[100]',
            'invoice_type' => 'permit_empty|in_list[standard,return,pickup,delivery]',
            'e_invoice_status' => 'permit_empty|in_list[pending,processing,issued,rejected,canceled]',
            'delivery_status' => 'permit_empty|in_list[pending,shipping,delivered,failed,returning]',
            'shipping_partner' => 'permit_empty|string|max_length[50]',
            'payment_method' => 'permit_empty|string|max_length[50]',
            'sales_channel' => 'permit_empty|string|max_length[50]',
            'created_by' => 'permit_empty|integer|greater_than_equal_to[1]',
            'seller_id' => 'permit_empty|integer|greater_than_equal_to[1]',
        ];
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid filters');
        }
        $validated = $this->v->getValidated();
        $validated['page'] = (int) ($validated['page'] ?? 1);
        $validated['limit'] = (int) ($validated['limit'] ?? 20);
        $validated['customer_id'] = isset($validated['customer_id']) ? (int) $validated['customer_id'] : null;
        $validated['branch_id'] = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;

        // Validate and add array fields
        $allowedStatuses = ['draft', 'processing', 'completed', 'failed_delivery', 'cancelled', 'void'];
        if ($invoiceStatus !== null && $invoiceStatus !== '') {
            $statuses = is_array($invoiceStatus) ? $invoiceStatus : [$invoiceStatus];
            foreach ($statuses as $s) {
                if (! in_array($s, $allowedStatuses, true)) {
                    throw new InvalidArgumentException("Invalid invoice_status value: {$s}");
                }
            }
            $validated['invoice_status'] = $statuses;
        }

        $allowedTypes = ['standard', 'return', 'pickup', 'delivery'];
        if ($invoiceTypes !== null && $invoiceTypes !== '' && ! empty($invoiceTypes)) {
            $types = is_array($invoiceTypes) ? $invoiceTypes : [$invoiceTypes];
            foreach ($types as $t) {
                if (! in_array($t, $allowedTypes, true)) {
                    throw new InvalidArgumentException("Invalid invoice_types value: {$t}");
                }
            }
            $validated['invoice_types'] = $types;
        }

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

    /** Validate update payload. */
    public function validateUpdate(array $input): array
    {
        $rules = [
            'due_date' => 'permit_empty|valid_date',
            'notes' => 'permit_empty|string',
            'invoice_status' => 'permit_empty|in_list[draft,processing,completed,failed_delivery,cancelled,void]',
            'e_invoice_status' => 'permit_empty|in_list[pending,processing,issued,rejected,canceled]',
            'delivery_status' => 'permit_empty|in_list[pending,shipping,delivered,failed,returning]',
            'shipment_code' => 'permit_empty|string|max_length[100]',
            'shipping_partner' => 'permit_empty|string|max_length[50]',
            'delivery_note' => 'permit_empty|string|max_length[500]',
            'delivery_time' => 'permit_empty|valid_date[Y-m-d H:i:s]',
        ];
        if (! $this->v->setRules($rules)->run($input)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }

        $allowed = ['due_date', 'notes', 'invoice_status', 'e_invoice_status', 'delivery_status', 'shipment_code', 'shipping_partner', 'delivery_note', 'delivery_time'];
        $result = [];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $input)) {
                $result[$key] = $input[$key];
            }
        }
        return $result;
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
