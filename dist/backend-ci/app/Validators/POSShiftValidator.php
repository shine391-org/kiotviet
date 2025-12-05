<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate POS shift lifecycle.
 *
 * @agent-validator: POS shift
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class POSShiftValidator
{
    /**
     * Validate opening payload.
     *
     * @agent-use: POST /api/pos/shifts/open
     * @agent-pattern: Guard single open shift
     */
    public function validateOpen(array $input): array
    {
        $userId = $this->positiveInt($input['user_id'] ?? null, 'user_id');
        $branchId = $this->positiveInt($input['branch_id'] ?? null, 'branch_id');
        $opening = isset($input['opening_balance']) ? (float) $input['opening_balance'] : 0.0;
        if ($opening < 0) {
            throw new InvalidArgumentException('opening_balance must be >= 0');
        }

        return [
            'user_id' => $userId,
            'branch_id' => $branchId,
            'profile_id' => $this->positiveIntOrNull($input['profile_id'] ?? null, 'profile_id'),
            'opening_balance' => $opening,
            'status' => 'open',
        ];
    }

    /**
     * Validate closing payload with actual counted payments.
     *
     * @agent-use: POST /api/pos/shifts/close
     * @agent-pattern: Reconcile expected vs actual
     */
    public function validateClose(array $input): array
    {
        $shiftId = $this->positiveInt($input['shift_id'] ?? null, 'shift_id');
        $actuals = $input['actual_payments'] ?? null;
        if (! is_array($actuals) || empty($actuals)) {
            throw new InvalidArgumentException('actual_payments is required');
        }
        $normalized = [];
        foreach ($actuals as $method => $amount) {
            $code = strtoupper(trim(is_string($method) ? $method : (string) ($amount['payment_method'] ?? '')));
            $value = is_array($amount) ? ($amount['amount'] ?? null) : $amount;
            $amt = (float) $value;
            if ($code === '' || $amt < 0) {
                throw new InvalidArgumentException('actual_payments entries must have method and amount >= 0');
            }
            $normalized[$code] = $amt;
        }
        return [
            'shift_id' => $shiftId,
            'actual_payments' => $normalized,
            'closing_note' => isset($input['closing_note']) ? trim((string) $input['closing_note']) : null,
        ];
    }

    /**
     * Validate payment log for shift expected totals.
     */
    public function validatePayment(array $input): array
    {
        $shiftId = $this->positiveInt($input['shift_id'] ?? null, 'shift_id');
        $method = strtoupper(trim((string) ($input['payment_method'] ?? '')));
        if ($method === '') {
            throw new InvalidArgumentException('payment_method is required for shift payment log');
        }
        $amount = isset($input['amount']) ? (float) $input['amount'] : 0;
        if ($amount <= 0) {
            throw new InvalidArgumentException('payment amount must be > 0');
        }

        return [
            'shift_id' => $shiftId,
            'order_id' => $this->positiveIntOrNull($input['order_id'] ?? null, 'order_id'),
            'payment_method' => $method,
            'amount' => $amount,
            'reference_type' => $input['reference_type'] ?? 'order',
            'reference_id' => $this->positiveIntOrNull($input['reference_id'] ?? null, 'reference_id'),
        ];
    }

    private function positiveInt($value, string $field): int
    {
        if ($value === null || $value === '') {
            throw new InvalidArgumentException("{$field} is required");
        }
        $int = (int) $value;
        if ($int <= 0) {
            throw new InvalidArgumentException("{$field} must be positive");
        }
        return $int;
    }

    private function positiveIntOrNull($value, string $field): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        $int = (int) $value;
        if ($int <= 0) {
            throw new InvalidArgumentException("{$field} must be positive");
        }
        return $int;
    }
}
