<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate order subscriptions.
 *
 * @agent-validator: Order subscriptions
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class OrderSubscriptionValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    /** Validate subscription creation. */
    public function validateCreate(array $input): array
    {
        $data = $this->run($input, [
            'template_id' => 'required|integer|greater_than[0]',
            'branch_id' => 'required|integer|greater_than[0]',
            'payment_method' => 'required|string|max_length[50]',
            'order_type' => 'permit_empty|string|max_length[20]',
            'next_run_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'frequency_interval' => 'permit_empty|integer|greater_than_equal_to[1]',
            'status' => 'permit_empty|string|max_length[20]',
        ]);

        return $this->normalize($data);
    }

    /** Validate subscription update (partial). */
    public function validateUpdate(array $input): array
    {
        $data = $this->run($input, [
            'branch_id' => 'permit_empty|integer|greater_than[0]',
            'payment_method' => 'permit_empty|string|max_length[50]',
            'order_type' => 'permit_empty|string|max_length[20]',
            'next_run_at' => 'permit_empty|valid_date[Y-m-d H:i:s]',
            'frequency_interval' => 'permit_empty|integer|greater_than_equal_to[1]',
            'status' => 'permit_empty|string|max_length[20]',
        ]);

        if (empty($data)) {
            throw new InvalidArgumentException('No data to update');
        }

        return $this->normalize($data);
    }

    private function normalize(array $data): array
    {
        if (! isset($data['order_type'])) {
            $data['order_type'] = 'shipping';
        }
        if (! isset($data['status'])) {
            $data['status'] = 'active';
        }
        if (isset($data['frequency_interval'])) {
            $data['frequency_interval'] = (int) $data['frequency_interval'];
        } else {
            $data['frequency_interval'] = 7;
        }

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
