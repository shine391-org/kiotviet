<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate subscriptions.
 *
 * @agent-validator: Subscriptions
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class SubscriptionValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /** Validate creation payload. */
    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'customer_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'template_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'plan_name' => 'required|string|max_length[150]',
            'interval_days' => 'permit_empty|integer|greater_than_equal_to[1]',
            'next_run_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'status' => 'permit_empty|string|max_length[30]',
        ]);

        $items = $input['items'] ?? null;
        $data['items'] = $this->validateItems($items);

        $data['interval_days'] = $data['interval_days'] ?? 30;
        $data['status'] = $data['status'] ?? 'active';
        return $data;
    }

    /** Validate update payload. */
    public function validateUpdate(array $input): array
    {
        $data = $this->run($input, [
            'customer_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'template_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'plan_name' => 'permit_empty|string|max_length[150]',
            'interval_days' => 'permit_empty|integer|greater_than_equal_to[1]',
            'next_run_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'status' => 'permit_empty|string|max_length[30]',
            'items' => 'permit_empty',
        ]);
        if (array_key_exists('items', $input)) {
            $data['items'] = $this->validateItems($input['items']);
        }
        if (empty($data)) {
            throw new InvalidArgumentException('No data to update');
        }
        return $data;
    }

    private function validateItems($items): array
    {
        if (! is_array($items) || empty($items)) {
            return [];
        }
        $normalized = [];
        foreach ($items as $idx => $item) {
            if (! is_array($item)) {
                throw new InvalidArgumentException('Invalid item at index ' . $idx);
            }
            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (float) ($item['quantity'] ?? 0);
            if ($productId <= 0) {
                throw new InvalidArgumentException('product_id is required for item ' . $idx);
            }
            if ($qty <= 0) {
                throw new InvalidArgumentException('quantity must be > 0 for item ' . $idx);
            }
            $normalized[] = [
                'product_id' => $productId,
                'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'quantity' => $qty,
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
