<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Danh sách type cho các trường đa hình (entity_type/reference_type).
 *
 * @agent-config: Polymorphic type whitelist
 * @agent-pattern: Allow-list + optional table mapping
 */
class PolymorphicTypes extends BaseConfig
{
    /**
     * Cấu trúc:
     *  [
     *      'context' => [
     *          'type_value' => ['table' => 'orders', 'checkId' => true],
     *          'another'    => ['table' => null, 'checkId' => false],
     *      ]
     *  ]
     */
    public array $contexts = [
        'approvals' => [
            'order' => ['table' => 'orders'],
            'invoice' => ['table' => 'invoices'],
            'return' => ['table' => 'returns'],
        ],
        'assignment_logs' => [
            'order' => ['table' => 'orders'],
            'customer' => ['table' => 'customers'],
        ],
        'audit_logs' => [
            'order' => ['table' => 'orders'],
            'invoice' => ['table' => 'invoices'],
            'customer' => ['table' => 'customers'],
            'product' => ['table' => 'products'],
        ],
        'document_shares' => [
            'order' => ['table' => 'orders'],
            'invoice' => ['table' => 'invoices'],
            'customer' => ['table' => 'customers'],
        ],
        'notifications' => [
            'order' => ['table' => 'orders'],
            'invoice' => ['table' => 'invoices'],
            'return' => ['table' => 'returns'],
        ],
        'cash_transactions' => [
            'order' => ['table' => 'orders'],
            'return_order' => ['table' => 'returns'],
            // Demo/ref data không có bảng riêng
            'demo_opening' => ['table' => null],
            'demo_delivery' => ['table' => null],
            'demo_return' => ['table' => null],
        ],
        'gl_entries' => [
            'order' => ['table' => 'orders'],
            'invoice' => ['table' => 'invoices'],
            'return' => ['table' => 'returns'],
        ],
        'inventory_movements' => [
            'order' => ['table' => 'orders'],
            'delivery_note' => ['table' => 'delivery_notes'],
            'return' => ['table' => 'returns'],
        ],
        'payment_entries' => [
            'order' => ['table' => 'orders'],
            'invoice' => ['table' => 'invoices'],
        ],
        'payment_entry_allocations' => [
            'order' => ['table' => 'orders'],
            'invoice' => ['table' => 'invoices'],
        ],
        'pos_shift_payments' => [
            'order' => ['table' => 'orders'],
        ],
        'price_history' => [
            'product' => ['table' => 'products'],
            'variant' => ['table' => 'product_variants_v2'],
        ],
        'quality_inspections' => [
            'order' => ['table' => 'orders'],
            'delivery_note' => ['table' => 'delivery_notes'],
        ],
        'stock_entries' => [
            'order' => ['table' => 'orders'],
            'return' => ['table' => 'returns'],
        ],
        'stock_ledgers' => [
            'demo_opening' => ['table' => null],
            'demo_delivery' => ['table' => null],
            'demo_return' => ['table' => null],
        ],
    ];
}
