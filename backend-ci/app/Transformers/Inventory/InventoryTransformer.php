<?php

namespace App\Transformers\Inventory;

/** Inventory transformers for API responses. @agent-transformer: Inventory @agent-pattern: Data formatter @agent-reusable: MEDIUM */
class InventoryTransformer
{
    public static function warehouse(array $row): array
    {
        return [
            'id' => isset($row['id']) ? (int) $row['id'] : 0,
            'code' => $row['code'] ?? '',
            'name' => $row['name'] ?? '',
            'status' => $row['status'] ?? 'active',
            'is_default' => (bool) ($row['is_default'] ?? false),
            'address' => $row['address'] ?? null,
            'phone' => $row['phone'] ?? null,
            'manager_id' => isset($row['manager_id']) ? (int) $row['manager_id'] : null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }

    public static function movement(array $row): array
    {
        return [
            'id' => isset($row['id']) ? (int) $row['id'] : 0,
            'reference_code' => $row['reference_code'] ?? '',
            'movement_type' => $row['movement_type'] ?? '',
            'product_id' => isset($row['product_id']) ? (int) $row['product_id'] : 0,
            'variant_id' => isset($row['variant_id']) ? (int) $row['variant_id'] : null,
            'from_warehouse_id' => isset($row['from_warehouse_id']) ? (int) $row['from_warehouse_id'] : null,
            'to_warehouse_id' => isset($row['to_warehouse_id']) ? (int) $row['to_warehouse_id'] : null,
            'quantity' => isset($row['quantity']) ? (float) $row['quantity'] : 0.0,
            'unit_cost' => isset($row['unit_cost']) ? (float) $row['unit_cost'] : 0.0,
            'total_cost' => isset($row['total_cost']) ? (float) $row['total_cost'] : null,
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    public static function alert(array $row): array
    {
        return [
            'id' => isset($row['id']) ? (int) $row['id'] : 0,
            'alert_type' => $row['alert_type'] ?? '',
            'product_id' => isset($row['product_id']) ? (int) $row['product_id'] : 0,
            'variant_id' => isset($row['variant_id']) ? (int) $row['variant_id'] : null,
            'warehouse_id' => isset($row['warehouse_id']) ? (int) $row['warehouse_id'] : 0,
            'current_quantity' => isset($row['current_quantity']) ? (float) $row['current_quantity'] : null,
            'threshold_quantity' => isset($row['threshold_quantity']) ? (float) $row['threshold_quantity'] : null,
            'status' => $row['status'] ?? 'active',
            'resolved_by' => isset($row['resolved_by']) ? (int) $row['resolved_by'] : null,
            'resolved_at' => $row['resolved_at'] ?? null,
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    public static function valuation(array $row): array
    {
        return [
            'id' => isset($row['id']) ? (int) $row['id'] : 0,
            'warehouse_id' => isset($row['warehouse_id']) ? (int) $row['warehouse_id'] : 0,
            'product_id' => isset($row['product_id']) ? (int) $row['product_id'] : 0,
            'variant_id' => isset($row['variant_id']) ? (int) $row['variant_id'] : null,
            'valuation_method' => $row['valuation_method'] ?? '',
            'quantity' => isset($row['quantity']) ? (float) $row['quantity'] : 0.0,
            'unit_cost' => isset($row['unit_cost']) ? (float) $row['unit_cost'] : 0.0,
            'total_value' => isset($row['total_value']) ? (float) $row['total_value'] : (isset($row['quantity']) && isset($row['unit_cost']) ? (float) $row['quantity'] * (float) $row['unit_cost'] : 0.0),
            'movement_id' => isset($row['movement_id']) ? (int) $row['movement_id'] : null,
            'created_at' => $row['created_at'] ?? null,
        ];
    }

    /** Map list helper. */
    public static function map(array $rows, callable $fn): array
    {
        return array_map($fn, $rows);
    }
}
