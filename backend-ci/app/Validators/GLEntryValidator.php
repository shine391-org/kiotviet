<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate GL entries.
 *
 * @agent-validator: GLEntry
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class GLEntryValidator
{
    public function validateEntry(array $entry): array
    {
        $accountId = isset($entry['account_id']) ? (int) $entry['account_id'] : 0;
        if ($accountId <= 0) {
            throw new InvalidArgumentException('account_id must be positive');
        }

        $debit = isset($entry['debit']) ? (float) $entry['debit'] : 0.0;
        $credit = isset($entry['credit']) ? (float) $entry['credit'] : 0.0;
        if ($debit < 0 || $credit < 0) {
            throw new InvalidArgumentException('debit/credit must be >= 0');
        }
        if ($debit === 0.0 && $credit === 0.0) {
            throw new InvalidArgumentException('either debit or credit must be > 0');
        }
        if ($debit > 0 && $credit > 0) {
            throw new InvalidArgumentException('cannot have both debit and credit');
        }

        $postingDate = $this->normalizeDate($entry['posting_date'] ?? null);

        return [
            'account_id' => $accountId,
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'party_type' => isset($entry['party_type']) ? trim((string) $entry['party_type']) : null,
            'party_id' => isset($entry['party_id']) ? (int) $entry['party_id'] : null,
            'reference_type' => isset($entry['reference_type']) ? trim((string) $entry['reference_type']) : null,
            'reference_id' => isset($entry['reference_id']) ? (int) $entry['reference_id'] : null,
            'remarks' => isset($entry['remarks']) ? trim((string) $entry['remarks']) : null,
            'posting_date' => $postingDate,
        ];
    }

    private function normalizeDate($date): string
    {
        if (! $date) {
            throw new InvalidArgumentException('posting_date is required');
        }
        try {
            $dt = new \DateTime($date);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('posting_date is invalid');
        }
        return $dt->format('Y-m-d');
    }
}
