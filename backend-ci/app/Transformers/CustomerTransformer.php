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
        $row['created_by'] = isset($row['created_by']) ? (int) $row['created_by'] : null;

        if (isset($row['current_debt'])) {
            $row['current_debt'] = (float) $row['current_debt'];
        }
        if (isset($row['total_sales'])) {
            $row['total_sales'] = (float) $row['total_sales'];
        }
        if (isset($row['total_sales_net'])) {
            $row['total_sales_net'] = (float) $row['total_sales_net'];
        }

        if (isset($row['customer_type'])) {
            $row['customer_type'] = strtoupper((string) $row['customer_type']);
        }

        if (isset($row['gender']) && $row['gender'] !== null) {
            $row['gender'] = strtoupper((string) $row['gender']);
        }

        if (isset($row['status']) && $row['status'] !== null) {
            $row['status'] = strtoupper((string) $row['status']);
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
