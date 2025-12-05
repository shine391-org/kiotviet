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

        $items = $input['items'] ?? [];
        if (! is_array($items)) {
            throw new InvalidArgumentException('items must be array');
        }
        $normalizedItems = [];
        foreach ($items as $item) {
            $taxName = trim((string) ($item['tax_name'] ?? ''));
            $itemRate = isset($item['rate_percent']) ? (float) $item['rate_percent'] : 0;
            if ($taxName === '' || $itemRate < 0) {
                throw new InvalidArgumentException('tax item invalid');
            }
            $normalizedItems[] = [
                'tax_name' => $taxName,
                'rate_percent' => $itemRate,
                'charge_type' => $item['charge_type'] ?? 'on_net_total',
            ];
        }
        if (empty($normalizedItems)) {
            $normalizedItems[] = [
                'tax_name' => $name,
                'rate_percent' => $rate,
                'charge_type' => 'on_net_total',
            ];
        }

        return [
            'name' => $name,
            'rate_percent' => $rate,
            'is_inclusive' => ! empty($input['is_inclusive']),
            'rounding_rule' => $rounding,
            'status' => $input['status'] ?? 'active',
            'items' => $normalizedItems,
        ];
    }
}
