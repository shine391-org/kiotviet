<?php

namespace App\Transformers;

/**
 * Format customer responses.
 *
 * @agent-transformer: Customers
 * @agent-pattern: Response shaping
 * @agent-reusable: HIGH
 */
class CustomerTransformer
{
    /**
     * Transform a single customer row.
     *
     * @agent-use: Customer API payloads
     * @agent-pattern: Cast & normalize
     */
    public function transform(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['organization_id'] = isset($row['organization_id']) ? (int) $row['organization_id'] : 1;
        $row['customer_group_id'] = isset($row['customer_group_id']) ? (int) $row['customer_group_id'] : null;

        if (isset($row['customer_type'])) {
            $row['customer_type'] = strtoupper((string) $row['customer_type']);
        }

        if (isset($row['gender']) && $row['gender'] !== null) {
            $row['gender'] = strtoupper((string) $row['gender']);
        }

        return $row;
    }

    /**
     * Transform list of customers.
     */
    public function transformList(array $rows): array
    {
        return array_map(fn ($row) => $this->transform($row), $rows);
    }
}
