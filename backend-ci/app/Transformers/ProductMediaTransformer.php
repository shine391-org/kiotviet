<?php

namespace App\Transformers;

/**
 * Shape media library responses for the API.
 *
 * @agent-transformer: Product media formatter
 * @agent-pattern: Response shaping
 * @agent-reusable: MEDIUM
 */
class ProductMediaTransformer
{
    /**
     * Add attachment flags and normalize types.
     *
     * @agent-use: Media listing responses
     * @agent-pattern: Map & normalize
     */
    public function transformList(array $rows, ?int $entityId = null): array
    {
        $formatted = [];
        foreach ($rows as $row) {
            $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
            $row['product_id'] = isset($row['product_id']) ? (int) $row['product_id'] : null;
            $row['variant_id'] = isset($row['variant_id']) ? (int) $row['variant_id'] : null;
            $row['is_primary'] = (bool) ($row['is_primary'] ?? false);
            $row['sort_order'] = isset($row['sort_order']) ? (int) $row['sort_order'] : 0;

            $variantProductId = isset($row['variant_product_id']) ? (int) $row['variant_product_id'] : null;
            $row['is_attached'] = $entityId
                ? (($row['product_id'] === $entityId) ||
                    ($row['variant_id'] === $entityId) ||
                    ($variantProductId === $entityId))
                : false;

            unset($row['variant_product_id']);
            $formatted[] = $row;
        }

        return $formatted;
    }
}
