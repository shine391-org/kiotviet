<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate bank reconciliation inputs.
 *
 * @agent-validator: BankReconciliation
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class BankReconciliationValidator
{
    public function validateImport(array $input): array
    {
        $lines = $input['lines'] ?? [];
        if (! is_array($lines) || empty($lines)) {
            throw new InvalidArgumentException('lines are required');
        }
        $normalized = [];
        foreach ($lines as $line) {
            $amount = isset($line['amount']) ? (float) $line['amount'] : 0;
            if ($amount === 0.0) {
                throw new InvalidArgumentException('amount must be non-zero');
            }
            $normalized[] = [
                'account_number' => trim((string) ($line['account_number'] ?? '')),
                'amount' => $amount,
                'currency' => isset($line['currency']) ? strtoupper(trim((string) $line['currency'])) : 'VND',
                'reference_no' => $line['reference_no'] ?? null,
                'reference_date' => isset($line['reference_date']) ? $this->normalizeDate($line['reference_date'])->format('Y-m-d') : null,
                'description' => $line['description'] ?? null,
                'status' => 'imported',
            ];
        }
        return $normalized;
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
