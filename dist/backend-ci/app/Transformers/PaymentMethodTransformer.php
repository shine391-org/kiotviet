<?php

namespace App\Transformers;

/**
 * Format payment method responses.
 *
 * @agent-transformer: Payment methods formatter
 * @agent-pattern: Response shaping
 * @agent-reusable: MEDIUM
 */
class PaymentMethodTransformer
{
    /**
     * Transform a single row.
     *
     * @agent-use: Payment methods API responses
     * @agent-pattern: Cast & normalize
     */
    public function transform(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['is_active'] = (bool) ($row['is_active'] ?? false);
        $row['display_order'] = isset($row['display_order']) ? (int) $row['display_order'] : 0;

        if (isset($row['name_translations']) && is_string($row['name_translations'])) {
            $row['name_translations'] = json_decode($row['name_translations'], true) ?: null;
        }

        return $row;
    }

    /**
     * Transform list of rows.
     */
    public function transformList(array $rows): array
    {
        return array_map(fn ($row) => $this->transform($row), $rows);
    }
}
