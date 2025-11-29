<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate withholding rules.
 *
 * @agent-validator: Withholding
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class WithholdingValidator
{
    public function validateCreate(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('name is required');
        }
        $rate = isset($input['rate_percent']) ? (float) $input['rate_percent'] : 0;
        if ($rate < 0) {
            throw new InvalidArgumentException('rate_percent must be >= 0');
        }
        $threshold = isset($input['apply_threshold']) ? (float) $input['apply_threshold'] : 0;
        if ($threshold < 0) {
            throw new InvalidArgumentException('apply_threshold must be >= 0');
        }
        return [
            'name' => $name,
            'rate_percent' => $rate,
            'apply_threshold' => $threshold,
            'status' => $input['status'] ?? 'active',
        ];
    }
}
