<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Bổ sung ràng buộc FK toàn diện và sửa dữ liệu nền (price lists).
 *
 * @agent-migration: Comprehensive FK hardening
 * @agent-pattern: Data cleanup + conditional FK add
 * @agent-reusable: MEDIUM
 */
class AddComprehensiveForeignKeys extends Migration
{
    public function up()
    {
        if ($this->isSqlite()) {
            // SQLite dev/test không hỗ trợ alter FK linh hoạt, bỏ qua.
            return;
        }

        $this->seedPriceLists();
        $this->fixPriceListOrphans();
        $this->alignPriceListColumns();
        $this->alignTaxTemplateColumns();

        foreach ($this->fkMap() as $table => $columns) {
            foreach ($columns as $link) {
                [$column, $refTable, $refColumn, $onDelete, $onUpdate] = $this->normalizeLink($link);
                $onDelete = $this->adjustOnDelete($table, $column, $onDelete);
                $constraint = $this->constraintName($table, $column, $refTable);

                if (! $this->canAddFk($table, $column, $refTable, $refColumn, $constraint)) {
                    continue;
                }

                $this->alignColumnToReference($table, $column, $refTable, $refColumn);

                $sql = "ALTER TABLE {$table} ADD CONSTRAINT {$constraint} FOREIGN KEY ({$column}) REFERENCES {$refTable}({$refColumn}) ON DELETE {$onDelete} ON UPDATE {$onUpdate}";
                $this->db->query($sql);
            }
        }
    }

    public function down()
    {
        if ($this->isSqlite()) {
            return;
        }

        foreach ($this->fkMap() as $table => $columns) {
            foreach ($columns as $link) {
                [$column, $refTable] = $this->normalizeLink($link);
                $constraint = $this->constraintName($table, $column, $refTable);
                if ($this->foreignKeyExists($table, $constraint)) {
                    $this->forge->dropForeignKey($table, $constraint);
                }
            }
        }
    }

    private function fkMap(): array
    {
        // [table => [[column, refTable, refColumn (default id), onDelete (default CASCADE), onUpdate (default CASCADE)]]]
        return [
            'appointments' => [
                ['customer_id', 'customers', 'id', 'SET NULL'],
                ['lead_id', 'leads', 'id', 'SET NULL'],
                ['contract_id', 'contracts', 'id', 'SET NULL'],
            ],
            'approval_actions' => [
                ['approval_id', 'approvals'],
                ['actor_id', 'users', 'id', 'SET NULL'],
            ],
            'approvals' => [
                ['order_id', 'orders', 'id', 'SET NULL'],
                ['current_approver_id', 'users', 'id', 'SET NULL'],
            ],
            'assignment_logs' => [
                ['assignee_id', 'users', 'id', 'SET NULL'],
                ['assignment_rule_id', 'assignment_rules', 'id', 'SET NULL'],
            ],
            'assignment_rules' => [
                ['last_assigned_id', 'users', 'id', 'SET NULL'],
            ],
            'attendances' => [
                ['employee_id', 'employees', 'id', 'SET NULL'],
            ],
            'attribute_options' => [
                ['attribute_id', 'attributes'],
            ],
            'audit_logs' => [
                ['actor_id', 'users', 'id', 'SET NULL'],
                ['company_id', 'companies'],
            ],
            'bank_reconciliation_logs' => [
                ['bank_reconciliation_id', 'bank_reconciliations'],
            ],
            'bank_reconciliations' => [
                ['bank_statement_id', 'bank_statements', 'id', 'SET NULL'],
                ['payment_entry_id', 'payment_entries', 'id', 'SET NULL'],
            ],
            'bill_of_materials' => [
                ['product_id', 'products'],
            ],
            'bom_items' => [
                ['bom_id', 'bill_of_materials'],
                ['component_product_id', 'products'],
            ],
            'campaign_members' => [
                ['campaign_id', 'campaigns'],
                ['customer_id', 'customers', 'id', 'SET NULL'],
                ['lead_id', 'leads', 'id', 'SET NULL'],
            ],
            'cash_transactions' => [
                ['branch_id', 'branches', 'id', 'SET NULL'],
                ['created_by', 'users', 'id', 'SET NULL'],
            ],
            'chart_of_accounts' => [
                ['parent_id', 'chart_of_accounts', 'id', 'SET NULL'],
            ],
            'company_permissions' => [
                ['company_id', 'companies'],
                ['user_id', 'users'],
            ],
            'contract_terms' => [
                ['contract_id', 'contracts'],
            ],
            'contracts' => [
                ['customer_id', 'customers', 'id', 'SET NULL'],
                ['template_id', 'contract_templates', 'id', 'SET NULL'],
            ],
            'coupon_usages' => [
                ['coupon_id', 'coupons'],
                ['customer_id', 'customers', 'id', 'SET NULL'],
                ['order_id', 'orders', 'id', 'SET NULL'],
            ],
            'credit_limits' => [
                ['customer_id', 'customers'],
            ],
            'customer_price_lists' => [
                ['customer_id', 'customers'],
                ['price_list_id', 'price_lists'],
            ],
            'delivery_note_items' => [
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['batch_id', 'product_batches', 'id', 'SET NULL'],
            ],
            'depreciation_schedule_lines' => [
                ['schedule_id', 'depreciation_schedules'],
                ['posted_gl_entry_id', 'gl_entries', 'id', 'SET NULL'],
            ],
            'depreciation_schedules' => [
                ['asset_id', 'assets'],
            ],
            'document_shares' => [
                ['company_id', 'companies'],
                ['shared_with_user_id', 'users'],
            ],
            'e_invoice_logs' => [
                ['invoice_id', 'invoices'],
            ],
            'email_campaign_logs' => [
                ['email_campaign_id', 'email_campaigns'],
                ['member_id', 'campaign_members'],
            ],
            'email_campaigns' => [
                ['campaign_id', 'campaigns'],
            ],
            'employees' => [
                ['branch_id', 'branches', 'id', 'SET NULL'],
            ],
            'gl_entries' => [
                ['account_id', 'chart_of_accounts'],
            ],
            'goods_receipt_items' => [
                ['goods_receipt_id', 'goods_receipts'],
                ['product_id', 'products'],
            ],
            'goods_receipts' => [
                ['branch_id', 'branches', 'id', 'SET NULL'],
                ['purchase_order_id', 'purchase_orders', 'id', 'SET NULL'],
            ],
            'inventory_alerts' => [
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['warehouse_id', 'warehouses'],
            ],
            'inventory_movements' => [
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['branch_id', 'branches', 'id', 'SET NULL'],
                ['batch_id', 'product_batches', 'id', 'SET NULL'],
            ],
            'inventory_stock' => [
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['branch_id', 'branches'],
            ],
            'inventory_valuation' => [
                ['movement_id', 'inventory_movements'],
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['warehouse_id', 'warehouses'],
            ],
            'invoices' => [
                ['customer_id', 'customers'],
                ['branch_id', 'branches'],
            ],
            'job_logs' => [
                ['job_id', 'job_queue'],
            ],
            'knowledge_base_articles' => [
                ['category_id', 'knowledge_base_categories'],
            ],
            'landed_cost_items' => [
                ['goods_receipt_item_id', 'goods_receipt_items'],
                ['landed_cost_voucher_id', 'landed_cost_vouchers'],
            ],
            'landed_cost_vouchers' => [
                ['goods_receipt_id', 'goods_receipts'],
            ],
            'leave_applications' => [
                ['employee_id', 'employees'],
                ['leave_type_id', 'leave_types'],
            ],
            'loyalty_transactions' => [
                ['order_id', 'orders', 'id', 'SET NULL'],
                ['wallet_id', 'loyalty_wallets'],
            ],
            'loyalty_wallets' => [
                ['customer_id', 'customers'],
            ],
            'maintenance_schedules' => [
                ['asset_id', 'assets'],
            ],
            'maintenance_work_orders' => [
                ['asset_id', 'assets'],
                ['schedule_id', 'maintenance_schedules', 'id', 'SET NULL'],
            ],
            'notifications' => [
                ['rule_id', 'notification_rules', 'id', 'SET NULL'],
            ],
            'opportunities' => [
                ['customer_id', 'customers', 'id', 'SET NULL'],
                ['lead_id', 'leads', 'id', 'SET NULL'],
            ],
            'opportunity_items' => [
                ['opportunity_id', 'opportunities'],
                ['product_id', 'products', 'id', 'SET NULL'],
            ],
            'order_approval_rules' => [
                ['customer_id', 'customers', 'id', 'SET NULL'],
            ],
            'order_items' => [
                ['order_id', 'orders'],
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['price_list_id', 'price_lists', 'id', 'SET NULL'],
                ['batch_id', 'product_batches', 'id', 'SET NULL'],
            ],
            'order_sequences' => [
                ['branch_id', 'branches'],
            ],
            'order_subscriptions' => [
                ['branch_id', 'branches'],
                ['template_id', 'order_templates'],
            ],
            'order_template_items' => [
                ['template_id', 'order_templates'],
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
            ],
            'order_templates' => [
                ['customer_id', 'customers', 'id', 'SET NULL'],
            ],
            'orders' => [
                ['tax_template_id', 'tax_templates', 'id', 'SET NULL'],
                ['warehouse_id', 'warehouses', 'id', 'SET NULL'],
                ['applied_price_list_id', 'price_lists', 'id', 'SET NULL'],
            ],
            'packing_slip_items' => [
                ['packing_slip_id', 'packing_slips'],
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['stock_entry_item_id', 'stock_entry_items', 'id', 'SET NULL'],
                ['pick_list_item_id', 'pick_list_items', 'id', 'SET NULL'],
                ['batch_id', 'product_batches', 'id', 'SET NULL'],
            ],
            'packing_slips' => [
                ['pick_list_id', 'pick_lists', 'id', 'SET NULL'],
                ['source_warehouse_id', 'warehouses', 'id', 'SET NULL'],
                ['target_warehouse_id', 'warehouses', 'id', 'SET NULL'],
                ['stock_entry_id', 'stock_entries', 'id', 'SET NULL'],
            ],
            'payment_entries' => [
                ['order_id', 'orders', 'id', 'SET NULL'],
                ['credit_account_id', 'chart_of_accounts'],
                ['debit_account_id', 'chart_of_accounts'],
            ],
            'payment_entry_allocations' => [
                ['payment_entry_id', 'payment_entries'],
            ],
            'payment_schedules' => [
                ['invoice_id', 'sales_invoices'],
            ],
            'pick_list_items' => [
                ['pick_list_id', 'pick_lists'],
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['stock_entry_item_id', 'stock_entry_items', 'id', 'SET NULL'],
                ['source_warehouse_id', 'warehouses', 'id', 'SET NULL'],
                ['target_warehouse_id', 'warehouses', 'id', 'SET NULL'],
                ['batch_id', 'product_batches', 'id', 'SET NULL'],
            ],
            'pick_lists' => [
                ['stock_entry_id', 'stock_entries', 'id', 'SET NULL'],
                ['source_warehouse_id', 'warehouses', 'id', 'SET NULL'],
            ],
            'portal_access_tokens' => [
                ['portal_user_id', 'portal_users'],
            ],
            'portal_users' => [
                ['customer_id', 'customers'],
            ],
            'pos_offline_queue' => [
                ['branch_id', 'branches'],
                ['order_id', 'orders', 'id', 'SET NULL'],
                ['user_id', 'users', 'id', 'SET NULL'],
            ],
            'pos_payment_methods' => [
                ['profile_id', 'pos_profiles'],
            ],
            'pos_profiles' => [
                ['branch_id', 'branches'],
                ['user_id', 'users', 'id', 'SET NULL'],
                ['role_id', 'roles', 'id', 'SET NULL'],
                ['warehouse_id', 'warehouses', 'id', 'SET NULL'],
                ['price_list_id', 'price_lists', 'id', 'SET NULL'],
                ['tax_template_id', 'tax_templates', 'id', 'SET NULL'],
            ],
            'pos_shifts' => [
                ['branch_id', 'branches'],
                ['profile_id', 'pos_profiles'],
                ['user_id', 'users', 'id', 'SET NULL'],
            ],
            'pos_shift_logs' => [
                ['shift_id', 'pos_shifts'],
            ],
            'pos_shift_payments' => [
                ['shift_id', 'pos_shifts'],
                ['order_id', 'orders', 'id', 'SET NULL'],
            ],
            'price_history' => [
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
            ],
            'price_list_items' => [
                ['price_list_id', 'price_lists'],
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
            ],
            'price_lists' => [
                ['base_price_list_id', 'price_lists', 'id', 'SET NULL'],
            ],
            'pricing_rules' => [
                ['customer_id', 'customers', 'id', 'SET NULL'],
                ['product_id', 'products', 'id', 'SET NULL'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['project_id', 'projects', 'id', 'SET NULL'],
            ],
            'product_attribute_options' => [
                ['attribute_id', 'product_attributes'],
            ],
            'product_attribute_values' => [
                ['attribute_id', 'product_attributes'],
                ['option_id', 'product_attribute_options'],
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
            ],
            'product_batches' => [
                ['branch_id', 'branches', 'id', 'SET NULL'],
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['warehouse_id', 'warehouses', 'id', 'SET NULL'],
            ],
            'product_categories' => [
                ['parent_id', 'product_categories', 'id', 'SET NULL'],
            ],
            'product_category_links' => [
                ['product_id', 'products'],
                ['category_id', 'product_categories'],
            ],
            'product_images' => [
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
            ],
            'product_serial_numbers' => [
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['batch_id', 'product_batches', 'id', 'SET NULL'],
                ['reserved_for_order_id', 'orders', 'id', 'SET NULL'],
                ['sold_to_order_id', 'orders', 'id', 'SET NULL'],
            ],
            'product_variants_v2' => [
                ['product_id', 'products'],
            ],
            'project_price_lists' => [
                ['project_id', 'projects'],
                ['price_list_id', 'price_lists'],
            ],
            'projects' => [
                ['customer_id', 'customers', 'id', 'SET NULL'],
            ],
            'purchase_invoice_items' => [
                ['invoice_id', 'purchase_invoices'],
                ['product_id', 'products', 'id', 'SET NULL'],
            ],
            'purchase_invoice_taxes' => [
                ['invoice_id', 'purchase_invoices'],
                ['template_id', 'tax_templates', 'id', 'SET NULL'],
            ],
            'purchase_orders' => [
                ['branch_id', 'branches', 'id', 'SET NULL'],
            ],
            'purchase_order_items' => [
                ['purchase_order_id', 'purchase_orders'],
                ['product_id', 'products', 'id', 'SET NULL'],
            ],
            'purchase_suggestions' => [
                ['branch_id', 'branches'],
                ['product_id', 'products'],
                ['purchase_order_id', 'purchase_orders', 'id', 'SET NULL'],
                ['reorder_level_id', 'reorder_levels', 'id', 'SET NULL'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
            ],
            'quality_inspection_items' => [
                ['inspection_id', 'quality_inspections'],
                ['parameter_id', 'quality_parameters'],
            ],
            'quotation_items' => [
                ['quotation_id', 'quotations'],
                ['product_id', 'products', 'id', 'SET NULL'],
            ],
            'quotations' => [
                ['customer_id', 'customers', 'id', 'SET NULL'],
                ['lead_id', 'leads', 'id', 'SET NULL'],
                ['opportunity_id', 'opportunities', 'id', 'SET NULL'],
            ],
            'reorder_levels' => [
                ['branch_id', 'branches'],
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
            ],
            'salary_components' => [
                ['salary_slip_id', 'salary_slips'],
            ],
            'salary_slips' => [
                ['employee_id', 'employees'],
                ['payroll_entry_id', 'payroll_entries', 'id', 'SET NULL'],
            ],
            'sales_invoice_items' => [
                ['invoice_id', 'sales_invoices'],
                ['product_id', 'products', 'id', 'SET NULL'],
            ],
            'sales_invoice_taxes' => [
                ['invoice_id', 'sales_invoices'],
                ['template_id', 'tax_templates', 'id', 'SET NULL'],
            ],
            'sales_invoices' => [
                ['customer_id', 'customers', 'id', 'SET NULL'],
                ['credit_account_id', 'chart_of_accounts', 'id', 'SET NULL'],
                ['debit_account_id', 'chart_of_accounts', 'id', 'SET NULL'],
            ],
            'sessions' => [
                ['user_id', 'users', 'id', 'SET NULL'],
            ],
            'stock_bins' => [
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['branch_id', 'branches'],
                ['batch_id', 'product_batches', 'id', 'SET NULL'],
            ],
            'stock_entries' => [
                ['branch_id', 'branches'],
                ['source_warehouse_id', 'warehouses', 'id', 'SET NULL'],
                ['target_warehouse_id', 'warehouses', 'id', 'SET NULL'],
            ],
            'stock_entry_items' => [
                ['stock_entry_id', 'stock_entries'],
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['source_branch_id', 'branches', 'id', 'SET NULL'],
                ['target_branch_id', 'branches', 'id', 'SET NULL'],
                ['source_warehouse_id', 'warehouses', 'id', 'SET NULL'],
                ['target_warehouse_id', 'warehouses', 'id', 'SET NULL'],
                ['batch_id', 'product_batches', 'id', 'SET NULL'],
            ],
            'stock_ledgers' => [
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['branch_id', 'branches', 'id', 'SET NULL'],
                ['warehouse_id', 'warehouses', 'id', 'SET NULL'],
            ],
            'stock_reconciliation_items' => [
                ['reconciliation_id', 'stock_reconciliations'],
                ['product_id', 'products'],
                ['variant_id', 'product_variants_v2', 'id', 'SET NULL'],
                ['batch_id', 'product_batches', 'id', 'SET NULL'],
            ],
            'stock_reconciliations' => [
                ['branch_id', 'branches'],
            ],
            'subcontracting_materials' => [
                ['subcontracting_order_id', 'subcontracting_orders'],
                ['material_product_id', 'products'],
            ],
            'subcontracting_orders' => [
                ['product_id', 'products'],
            ],
            'subscription_cycles' => [
                ['subscription_id', 'subscriptions'],
                ['order_id', 'orders', 'id', 'SET NULL'],
            ],
            'subscriptions' => [
                ['customer_id', 'customers', 'id', 'SET NULL'],
                ['template_id', 'order_templates', 'id', 'SET NULL'],
            ],
            'support_tickets' => [
                ['customer_id', 'customers', 'id', 'SET NULL'],
                ['lead_id', 'leads', 'id', 'SET NULL'],
            ],
            'tasks' => [
                ['parent_id', 'tasks', 'id', 'SET NULL'],
                ['project_id', 'projects', 'id', 'SET NULL'],
            ],
            'tax_certificate_records' => [
                ['party_id', 'customers', 'id', 'SET NULL'],
            ],
            'tax_charges' => [
                ['template_id', 'tax_templates'],
            ],
            'tax_template_items' => [
                ['template_id', 'tax_templates'],
            ],
            'ticket_communications' => [
                ['ticket_id', 'support_tickets'],
            ],
            'ticket_events' => [
                ['ticket_id', 'support_tickets'],
            ],
            'timesheet_details' => [
                ['timesheet_id', 'timesheets'],
                ['project_id', 'projects', 'id', 'SET NULL'],
                ['task_id', 'tasks', 'id', 'SET NULL'],
                ['activity_type_id', 'activity_types', 'id', 'SET NULL'],
            ],
            'timesheets' => [
                ['employee_id', 'employees', 'id', 'SET NULL'],
                ['project_id', 'projects', 'id', 'SET NULL'],
            ],
            'users' => [
                ['branch_id', 'branches', 'id', 'SET NULL'],
            ],
            'warehouses' => [
                ['branch_id', 'branches'],
            ],
            'work_orders' => [
                ['bom_id', 'bill_of_materials', 'id', 'SET NULL'],
                ['product_id', 'products'],
                ['branch_id', 'branches', 'id', 'SET NULL'],
            ],
        ];
    }

    private function seedPriceLists(): void
    {
        if (! $this->db->tableExists('price_lists')) {
            return;
        }
        $now = date('Y-m-d H:i:s');
        $rows = [
            [
                'id' => 1,
                'name' => 'Standard',
                'type' => 'base',
                'description' => 'Default price list',
                'priority' => 0,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 3,
                'name' => 'Flash Sale 30%',
                'type' => 'custom',
                'description' => 'Demo flash sale 30%',
                'priority' => 10,
                'is_active' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        foreach ($rows as $row) {
            $this->db->table('price_lists')->ignore(true)->insert($row);
        }
    }

    private function fixPriceListOrphans(): void
    {
        if (! $this->db->tableExists('price_lists')) {
            return;
        }
        // Nếu price list id không tồn tại, set NULL để thêm FK an toàn.
        $this->nullifyIfMissing('order_items', 'price_list_id', 'price_lists');
        $this->nullifyIfMissing('orders', 'applied_price_list_id', 'price_lists');
    }

    private function alignPriceListColumns(): void
    {
        $columns = [
            ['order_items', 'price_list_id'],
            ['orders', 'applied_price_list_id'],
            ['pos_profiles', 'price_list_id'],
            ['project_price_lists', 'price_list_id'],
            ['price_list_items', 'price_list_id'],
        ];

        foreach ($columns as [$table, $column]) {
            if (! $this->db->tableExists($table) || ! $this->columnExists($table, $column)) {
                continue;
            }
            // Đồng bộ kiểu BIGINT UNSIGNED NULL để tương thích price_lists.id
            $sql = "ALTER TABLE {$table} MODIFY {$column} BIGINT UNSIGNED NULL";
            $this->db->query($sql);
        }
    }

    private function alignTaxTemplateColumns(): void
    {
        $columns = [
            ['orders', 'tax_template_id'],
            ['pos_profiles', 'tax_template_id'],
            ['sales_invoice_taxes', 'template_id'],
            ['purchase_invoice_taxes', 'template_id'],
            ['tax_charges', 'template_id'],
            ['tax_template_items', 'template_id'],
        ];

        foreach ($columns as [$table, $column]) {
            if (! $this->db->tableExists($table) || ! $this->columnExists($table, $column)) {
                continue;
            }
            $sql = "ALTER TABLE {$table} MODIFY {$column} BIGINT UNSIGNED NULL";
            $this->db->query($sql);
        }
    }

    private function nullifyIfMissing(string $table, string $column, string $refTable): void
    {
        if (! $this->db->tableExists($table) || ! $this->db->tableExists($refTable)) {
            return;
        }
        $sql = "UPDATE {$table} t
                LEFT JOIN {$refTable} r ON t.{$column} = r.id
                SET t.{$column} = NULL
                WHERE t.{$column} IS NOT NULL AND r.id IS NULL";
        $this->db->query($sql);
    }

    private function canAddFk(string $table, string $column, string $refTable, string $refColumn, string $constraint): bool
    {
        return $this->db->tableExists($table)
            && $this->db->tableExists($refTable)
            && $this->columnExists($table, $column)
            && $this->columnExists($refTable, $refColumn)
            && ! $this->foreignKeyExists($table, $constraint);
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $sql = "SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?";
        $row = $this->db->query($sql, [$this->db->getDatabase(), $table, $constraint])->getRowArray();
        return ! empty($row);
    }

    private function columnExists(string $table, string $column): bool
    {
        $sql = "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1";
        $row = $this->db->query($sql, [$this->db->getDatabase(), $table, $column])->getRowArray();
        return ! empty($row);
    }

    private function constraintName(string $table, string $column, string $refTable): string
    {
        $name = 'fk_' . substr($table, 0, 20) . '_' . substr($column, 0, 20);
        return $name;
    }

    private function alignColumnToReference(string $table, string $column, string $refTable, string $refColumn): void
    {
        $colInfo = $this->fullColumnInfo($table, $column);
        $refInfo = $this->fullColumnInfo($refTable, $refColumn);

        if (empty($colInfo) || empty($refInfo)) {
            return;
        }

        $colType = strtolower($colInfo['COLUMN_TYPE'] ?? '');
        $refType = strtolower($refInfo['COLUMN_TYPE'] ?? '');

        if ($colType === $refType) {
            return;
        }

        $isNullable = strtoupper((string) ($colInfo['IS_NULLABLE'] ?? 'YES')) === 'YES';
        $nullClause = $isNullable ? 'NULL' : 'NOT NULL';
        $defaultClause = '';
        if (array_key_exists('COLUMN_DEFAULT', $colInfo) && $colInfo['COLUMN_DEFAULT'] !== null) {
            $defaultValue = $this->db->escape($colInfo['COLUMN_DEFAULT']);
            $defaultClause = " DEFAULT {$defaultValue}";
        } elseif ($isNullable) {
            $defaultClause = ' DEFAULT NULL';
        }

        $sql = "ALTER TABLE {$table} MODIFY {$column} {$refInfo['COLUMN_TYPE']} {$nullClause}{$defaultClause}";
        $this->db->query($sql);
    }

    private function fullColumnInfo(string $table, string $column): array
    {
        $sql = "SELECT COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1";
        return $this->db->query($sql, [$this->db->getDatabase(), $table, $column])->getRowArray() ?? [];
    }

    private function normalizeLink(array $link): array
    {
        $column = $link[0];
        $refTable = $link[1];
        $refColumn = $link[2] ?? 'id';
        $onDelete = $link[3] ?? 'CASCADE';
        $onUpdate = $link[4] ?? 'CASCADE';

        return [$column, $refTable, $refColumn, $onDelete, $onUpdate];
    }

    private function adjustOnDelete(string $table, string $column, string $onDelete): string
    {
        if ($onDelete !== 'SET NULL') {
            return $onDelete;
        }

        $info = $this->columnInfo($table, $column);
        if (empty($info)) {
            return $onDelete;
        }

        $isNullable = strtoupper((string) ($info['IS_NULLABLE'] ?? 'YES')) === 'YES';
        return $isNullable ? $onDelete : 'CASCADE';
    }

    private function columnInfo(string $table, string $column): array
    {
        $sql = "SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1";
        return $this->db->query($sql, [$this->db->getDatabase(), $table, $column])->getRowArray() ?? [];
    }

    private function isSqlite(): bool
    {
        return strtolower($this->db->DBDriver) === 'sqlite3';
    }
}
