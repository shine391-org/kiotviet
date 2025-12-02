<?php

namespace Tests\Support\Database;

use Config\Database;
use CodeIgniter\Database\BaseConnection;

/**
 * DevDatabaseTrait - dùng golden migration TestSchemaSetup cho group "tests",
 * chỉ truncate data trước mỗi test để tránh mất bảng.
 */
trait DevDatabaseTrait
{
    private static bool $schemaReady = false;

    protected function setUpDatabase(): void
    {
        // Luôn ép defaultGroup sang 'tests' để FeatureTestTrait/Services dùng cùng DB
        $config = config('Database');
        $config->defaultGroup = 'tests';
        \Config\Services::reset(true);

        $this->ensureSchema();
        $this->db = Database::connect('tests');
        $this->truncateData(); // Restored for correctness
        $this->db->transBegin();
        $this->bootstrapTestData();
    }

    protected function tearDownDatabase(): void
    {
        if (isset($this->db) && $this->db->connID) {
            if ($this->db->transDepth > 0) {
                $this->db->transRollback();
            }
            // $this->truncateData(); // Removed for performance
            $this->db->close();
        }
    }

    protected function forceFreshMigrate(): void
    {
        $this->ensureSchema();
        $this->db = Database::connect('tests');
        $this->truncateData();
        $this->db->transBegin();
    }

    private function ensureSchema(): void
    {
        $db = Database::connect('tests');
        $schemaStale = $this->schemaIsStale($db);
        $migrationsEmpty = $this->migrationsTableEmpty($db);
        $needsMigrate = $schemaStale || $migrationsEmpty;

        if ($schemaStale) {
            $this->rebuildSchema($db);
            $db = Database::connect('tests');
        }

        if (! $needsMigrate) {
            self::$schemaReady = true;
            return;
        }

        if ($needsMigrate) {
            $start = microtime(true);
            $migrations = \Config\Services::migrations();
            $migrations->setGroup('tests');
            $migrations->latest();
            self::$schemaReady = true;

            if (env('MIGRATION_VERBOSE', false)) {
                $duration = round(microtime(true) - $start, 3);
                error_log("DEBUG: DevDatabaseTrait - Migration (tests group) completed in {$duration}s");
            }
        }
    }

    private function schemaIsStale(BaseConnection $db): bool
    {
        $requiredTables = ['orders', 'customer_groups', 'organizations', 'payment_methods'];
        foreach ($requiredTables as $table) {
            if (! $db->tableExists($table)) {
                return true;
            }
        }

        $requiredOrderColumns = ['order_number', 'order_type', 'payment_method', 'shipping_fee', 'paid_amount', 'debt_amount'];
        foreach ($requiredOrderColumns as $column) {
            if (! $db->fieldExists($column, 'orders')) {
                return true;
            }
        }

        return false;
    }

    private function migrationsTableEmpty(BaseConnection $db): bool
    {
        if (! $db->tableExists('migrations')) {
            return true;
        }

        try {
            return $db->table('migrations')->countAllResults() === 0;
        } catch (\Throwable $e) {
            return true;
        }
    }

    private function rebuildSchema(BaseConnection $db): void
    {
        $db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($db->listTables() as $table) {
            // Drop everything (including migrations) to force clean rebuild
            $db->query('DROP TABLE IF EXISTS `' . $table . '`');
        }
        $db->query('SET FOREIGN_KEY_CHECKS=1');
        self::$schemaReady = false;
    }

    /**
     * Hook cho test muốn seed thêm dữ liệu sau truncate + transBegin.
     */
    protected function bootstrapTestData(): void
    {
        // override trong test nếu cần
    }

    /**
     * Truncate các bảng core, có kiểm tra tồn tại để tránh lỗi 1146.
     */
    private function truncateData(): void
    {
        $tables = [
            'invoice_orders','invoices',
            'return_items','returns',
            'order_items','order_payments','order_sequences','order_status_logs','orders',
            'price_list_items','price_lists',
            'product_attribute_values','product_attribute_options','product_attributes',
            'product_images','product_category_links','product_categories',
            'product_variants_v2','product_batches','product_serial_numbers','delivery_note_items','delivery_notes','products',
            'customers','companies','company_permissions','document_shares','audit_logs','branches','warehouses','users','factories',
            'inventory_alerts','inventory_movements','inventory_stock','inventory_valuation',
            'webhook_events','webhook_subscriptions',
            'approval_actions','approvals','order_approval_rules',
            'tax_certificate_records','e_invoice_logs','regional_tax_rules',
            'job_logs','job_queue','scheduler_rules',
            'maintenance_work_orders','maintenance_schedules','depreciation_schedule_lines','depreciation_schedules','assets',
            'salary_components','salary_slips','payroll_entries','attendances','leave_applications','leave_types','employees',
            'timesheet_details','timesheets','activity_types','tasks','projects',
            'stock_entry_items','stock_entries','pick_list_items','pick_lists','packing_slip_items','packing_slips',
            'stock_reconciliation_items','stock_reconciliations','stock_bins','stock_ledgers','reorder_levels','purchase_suggestions',
            'pricing_rules','customer_price_lists','project_price_lists','price_history',
            'order_template_items','order_templates','order_subscriptions',
            'ecommerce_webhook_logs',
            'subscription_cycles','subscriptions',
            'bom_items','bill_of_materials','work_orders',
            'quality_inspection_items','quality_inspections','quality_parameters',
            'cash_transactions','payment_methods','purchase_orders',
            'purchase_order_items','goods_receipt_items','goods_receipts','landed_cost_items','landed_cost_vouchers','subcontracting_materials','subcontracting_orders',
            'gl_entries','chart_of_accounts',
            'payment_schedules','sales_invoice_taxes','sales_invoice_items','sales_invoices',
            'purchase_invoice_taxes','purchase_invoice_items','purchase_invoices',
            'bank_reconciliation_logs','bank_reconciliations','bank_statements','payment_entry_allocations',
            'withholding_rules','credit_limits','tax_template_items','exchange_rates',
            'tax_charges','tax_templates',
            'payment_entries',
            'coupons','coupon_usages',
            'loyalty_transactions','loyalty_wallets','loyalty_programs',
            'quotation_items','quotations',
            'opportunity_items','opportunities',
            'leads',
            'campaign_members','campaigns','email_campaign_logs','email_campaigns',
            'ticket_events','ticket_communications','support_tickets',
            'contract_terms','contracts','contract_templates','appointments',
            'pos_offline_queue',
            'pos_shift_logs','pos_shift_payments','pos_shifts','pos_payment_methods','pos_profiles'
        ];
        $existing = array_flip($this->db->listTables());
        $this->db->query('SET FOREIGN_KEY_CHECKS=0');
        foreach ($tables as $table) {
            if (isset($existing[$table])) {
                try {
                    $this->db->table($table)->truncate();
                } catch (\Throwable $e) {
                    // Nếu bảng vừa bị drop bởi test khác, bỏ qua để không làm vỡ suite
                }
            }
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS=1');
    }
}
