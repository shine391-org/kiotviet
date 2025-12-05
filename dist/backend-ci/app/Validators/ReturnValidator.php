<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate return requests.
 *
 * @agent-validator: Returns
 * @agent-pattern: Validation first
 * @agent-reusable: HIGH
 */
class ReturnValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /** Validate create payload. */
    public function validateCreate(array $input): array
    {
        $rules = [
            'order_id' => 'required|integer|greater_than_equal_to[1]',
            'customer_id' => 'required|integer|greater_than_equal_to[1]',
            'items' => 'required',
            'reason' => 'required|in_list[defective,wrong_item,not_satisfied,other]',
            'reason_detail' => 'permit_empty|string',
            'refund_shipping_fee' => 'permit_empty|in_list[0,1,true,false]',
            'refund_method' => 'permit_empty|in_list[cash,bank_transfer]',
            'created_by' => 'permit_empty|integer|greater_than_equal_to[1]',
        ];
        if (! $this->v->setRules($rules)->run($input)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }

        $items = $this->normalizeItems($input['items']);
        if (empty($items)) {
            throw new InvalidArgumentException('Return must have at least one item');
        }
        $reason = $input['reason'];
        if ($reason === 'other' && empty($input['reason_detail'])) {
            throw new InvalidArgumentException('Reason detail is required for "other" reason');
        }

        return [
            'order_id' => (int) $input['order_id'],
            'customer_id' => (int) $input['customer_id'],
            'items' => $items,
            'reason' => $reason,
            'reason_detail' => isset($input['reason_detail']) ? trim((string) $input['reason_detail']) : null,
            'refund_shipping_fee' => array_key_exists('refund_shipping_fee', $input)
                ? (bool) filter_var($input['refund_shipping_fee'], FILTER_VALIDATE_BOOLEAN)
                : false,
            'refund_method' => $input['refund_method'] ?? null,
            'created_by' => isset($input['created_by']) ? (int) $input['created_by'] : null,
        ];
    }

    /** Validate status transition optimistic lock. */
    public function validateTransition(array $input): array
    {
        $rules = [
            'version' => 'permit_empty|integer|greater_than_equal_to[0]',
            'user_id' => 'required|integer|greater_than_equal_to[1]',
        ];
        if (! $this->v->setRules($rules)->run($input)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return [
            'version' => isset($input['version']) ? (int) $input['version'] : null,
            'user_id' => (int) $input['user_id'],
        ];
    }

    /** Validate approval payload. */
    public function validateApproval(array $input): array
    {
        $rules = [
            'user_id' => 'required|integer|greater_than_equal_to[1]',
            'refund_method' => 'required|in_list[cash,bank_transfer]',
            'refund_shipping_fee' => 'permit_empty|in_list[0,1,true,false]',
            'version' => 'permit_empty|integer|greater_than_equal_to[0]',
            'notes' => 'permit_empty|string',
        ];
        if (! $this->v->setRules($rules)->run($input)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return [
            'user_id' => (int) $input['user_id'],
            'refund_method' => $input['refund_method'],
            'refund_shipping_fee' => array_key_exists('refund_shipping_fee', $input)
                ? (bool) filter_var($input['refund_shipping_fee'], FILTER_VALIDATE_BOOLEAN)
                : null,
            'version' => isset($input['version']) ? (int) $input['version'] : null,
            'notes' => isset($input['notes']) ? trim((string) $input['notes']) : null,
        ];
    }

    private function normalizeItems($items): array
    {
        if (! is_array($items)) {
            throw new InvalidArgumentException('items must be an array');
        }
        $result = [];
        foreach ($items as $item) {
            $oid = (int) ($item['order_item_id'] ?? 0);
            $qty = (float) ($item['quantity_returned'] ?? 0);
            $condition = $item['condition'] ?? $item['item_condition'] ?? null;
            if ($oid <= 0) {
                throw new InvalidArgumentException('order_item_id is required');
            }
            if ($qty <= 0) {
                throw new InvalidArgumentException('quantity_returned must be > 0');
            }
            if ($condition !== null && ! in_array($condition, ['new', 'used', 'damaged'], true)) {
                throw new InvalidArgumentException('condition is invalid');
            }
            $result[] = [
                'order_item_id' => $oid,
                'quantity_returned' => $qty,
                'item_condition' => $condition,
            ];
        }
        return $result;
    }
}
