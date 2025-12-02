<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate payment entry operations.
 *
 * @agent-validator: Payment entry
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class PaymentEntryValidator
{
    public function validateCreate(array $input): array
    {
        $orderId = isset($input['order_id']) ? (int) $input['order_id'] : null;
        $partyType = isset($input['party_type']) ? trim((string) $input['party_type']) : null;
        $partyId = isset($input['party_id']) ? (int) $input['party_id'] : null;

        if (($orderId === null || $orderId <= 0) && ($partyType === null || $partyId === null || $partyId <= 0)) {
            throw new InvalidArgumentException('order_id or party_type/party_id is required');
        }

        $method = strtoupper(trim((string) ($input['payment_method'] ?? ($input['mode_of_payment'] ?? ''))));
        if ($method === '') {
            throw new InvalidArgumentException('payment_method is required');
        }

        $amount = isset($input['amount']) ? (float) $input['amount'] : 0;
        if ($amount <= 0) {
            throw new InvalidArgumentException('amount must be > 0');
        }

        $debitAcc = isset($input['debit_account_id']) ? (int) $input['debit_account_id'] : null;
        $creditAcc = isset($input['credit_account_id']) ? (int) $input['credit_account_id'] : null;
        if ($partyType !== null && ($debitAcc === null || $debitAcc <= 0 || $creditAcc === null || $creditAcc <= 0)) {
            throw new InvalidArgumentException('debit_account_id and credit_account_id are required');
        }

        $currency = isset($input['currency']) ? strtoupper(trim((string) $input['currency'])) : 'VND';
        $exchangeRate = isset($input['exchange_rate']) ? (float) $input['exchange_rate'] : 1;
        if ($exchangeRate <= 0) {
            throw new InvalidArgumentException('exchange_rate must be positive');
        }

        return [
            'order_id' => $orderId,
            'party_type' => $partyType,
            'party_id' => $partyId,
            'payment_method' => $method,
            'mode_of_payment' => $input['mode_of_payment'] ?? null,
            'amount' => $amount,
            'currency' => $currency,
            'exchange_rate' => $exchangeRate,
            'reference' => isset($input['reference']) ? trim((string) $input['reference']) : null,
            'reference_no' => isset($input['reference_no']) ? trim((string) $input['reference_no']) : null,
            'reference_date' => isset($input['reference_date']) ? $this->normalizeDate($input['reference_date'])->format('Y-m-d') : null,
            'reference_type' => $input['reference_type'] ?? null,
            'reference_id' => isset($input['reference_id']) ? (int) $input['reference_id'] : null,
            'debit_account_id' => $debitAcc,
            'credit_account_id' => $creditAcc,
            'status' => $input['status'] ?? 'posted',
        ];
    }

    private function normalizeDate($date): \DateTimeInterface
    {
        try {
            return new \DateTime($date);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('invalid date');
        }
    }
}
