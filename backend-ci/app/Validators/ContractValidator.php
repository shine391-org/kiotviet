<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate contracts.
 *
 * @agent-validator: Contract
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class ContractValidator
{
    public function validate(array $input): array
    {
        $customerId = isset($input['customer_id']) ? (int) $input['customer_id'] : null;
        if ($customerId !== null && $customerId <= 0) {
            throw new InvalidArgumentException('customer_id must be positive');
        }
        $startRaw = $input['start_date'] ?? date('Y-m-d');
        $start = $this->asDate($startRaw, 'start_date');
        $end = $input['end_date'] ?? null;
        $endDate = $end !== null ? $this->asDate($end, 'end_date') : null;
        if ($endDate && $endDate < $start) {
            throw new InvalidArgumentException('end_date must be after start_date');
        }
        $value = isset($input['value']) ? (float) $input['value'] : 0;
        if ($value < 0) {
            throw new InvalidArgumentException('value must be >= 0');
        }
        $terms = $input['terms'] ?? [];
        if (! is_array($terms)) {
            throw new InvalidArgumentException('terms must be array');
        }

        return [
            'customer_id' => $customerId,
            'template_id' => isset($input['template_id']) && (int) $input['template_id'] > 0 ? (int) $input['template_id'] : null,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $endDate?->format('Y-m-d'),
            'value' => $value,
            'status' => $input['status'] ?? 'draft',
            'auto_renew' => ! empty($input['auto_renew']),
            'terms' => array_map(
                fn ($t) => [
                    'description' => $t['description'] ?? null,
                    'is_completed' => ! empty($t['is_completed']),
                ],
                $terms
            ),
        ];
    }

    private function asDate(string $value, string $field): \DateTimeInterface
    {
        try {
            return new \DateTime($value);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException($field . ' is invalid');
        }
    }
}
