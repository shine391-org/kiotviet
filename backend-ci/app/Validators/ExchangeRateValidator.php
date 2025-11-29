<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate exchange rates.
 *
 * @agent-validator: ExchangeRate
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class ExchangeRateValidator
{
    public function validateCreate(array $input): array
    {
        $currency = strtoupper(trim((string) ($input['currency'] ?? '')));
        if ($currency === '') {
            throw new InvalidArgumentException('currency is required');
        }
        $rate = isset($input['rate']) ? (float) $input['rate'] : 0;
        if ($rate <= 0) {
            throw new InvalidArgumentException('rate must be > 0');
        }
        $validFrom = $this->normalizeDate($input['valid_from'] ?? null);

        return [
            'currency' => $currency,
            'rate' => $rate,
            'valid_from' => $validFrom->format('Y-m-d'),
        ];
    }

    private function normalizeDate($date): \DateTimeInterface
    {
        if (! $date) {
            throw new InvalidArgumentException('valid_from is required');
        }
        try {
            return new \DateTime($date);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('valid_from is invalid');
        }
    }
}
