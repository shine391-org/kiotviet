<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

class OrderPaymentValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateCreate(array $input): array
    {
        $rules = [
            'order_id' => 'required|integer|greater_than_equal_to[1]',
            'payment_method' => 'required|in_list[CASH,BANK_TRANSFER,CARD,COD,EWALLET]',
            'amount' => 'required|decimal|greater_than[0]',
            'paid_at' => 'permit_empty|valid_date',
        ];
        if (! $this->v->setRules($rules)->run($input)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid payment');
        }
        return [
            'order_id' => (int) $input['order_id'],
            'payment_method' => $input['payment_method'],
            'amount' => (float) $input['amount'],
            'paid_at' => $input['paid_at'] ?? null,
        ];
    }
}
