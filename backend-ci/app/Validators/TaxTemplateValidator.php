<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate tax template inputs.
 *
 * @agent-validator: Tax template
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class TaxTemplateValidator
{
    public function validateCreate(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('name is required');
        }
        $rate = isset($input['rate_percent']) ? (float) $input['rate_percent'] : 0.0;
        if ($rate < 0) {
            throw new InvalidArgumentException('rate_percent must be >= 0');
        }
        $rounding = $input['rounding_rule'] ?? 'nearest';

        return [
            'name' => $name,
            'rate_percent' => $rate,
            'is_inclusive' => ! empty($input['is_inclusive']),
            'rounding_rule' => $rounding,
            'status' => $input['status'] ?? 'active',
        ];
    }
}
