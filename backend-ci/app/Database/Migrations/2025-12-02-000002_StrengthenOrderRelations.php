<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Harden order-related schema: missing columns, FK cleanup, payment code normalization.
 *
 * @agent-migration: Orders FK hardening
 * @agent-pattern: Cleanup + constraints + data normalization
 */
class StrengthenOrderRelations extends Migration
{
    public function up()
    {
        if ($this->isSqlite()) {
            return;
        }

        $this->ensureOrderColumns();
        $this->normalizePaymentCodes();
        $this->syncOrderPaymentsSchema();
        $this->addOrderForeignKeys();
        $this->addReturnForeignKeys();
        $this->addDeliveryForeignKeys();
        $this->addCashTransactionForeignKeys();
    }

    public function down()
    {
        if ($this->isSqlite()) {
            return;
        }

        $this->dropFkIfExists('orders', 'fk_orders_customer');
        $this->dropFkIfExists('orders', 'fk_orders_branch');
        $this->dropFkIfExists('orders', 'fk_orders_payment_method');
        $this->dropFkIfExists('order_payments', 'fk_order_payments_order');
        $this->dropFkIfExists('order_payments', 'fk_order_payments_method');
        $this->dropFkIfExists('returns', 'fk_returns_order');
        $this->dropFkIfExists('returns', 'fk_returns_customer');
        $this->dropFkIfExists('delivery_notes', 'fk_delivery_notes_order');
        $this->dropFkIfExists('delivery_notes', 'fk_delivery_notes_customer');
        $this->dropFkIfExists('delivery_notes', 'fk_delivery_notes_branch');
        $this->dropFkIfExists('delivery_note_items', 'fk_delivery_note_items_note');
        $this->dropFkIfExists('delivery_note_items', 'fk_delivery_note_items_order_item');
        $this->dropFkIfExists('cash_transactions', 'fk_cash_transactions_branch');
        $this->dropFkIfExists('cash_transactions', 'fk_cash_transactions_user');
    }

    private function ensureOrderColumns(): void
    {
        if (! $this->db->tableExists('orders')) {
            return;
        }

        $fields = [];
        $add = function (string $column, array $definition) use (&$fields) {
            if (! $this->db->fieldExists($column, 'orders')) {
                $fields[$column] = $definition;
            }
        };

        $add('branch_id', ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'customer_group_id']);
        $add('warehouse_id', ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'branch_id']);
        $add('code', ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'comment' => 'Business code', 'after' => 'order_number']);
        $add('pos_profile_id', ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'order_type']);
        $add('pos_shift_id', ['type' => 'BIGINT', 'unsigned' => true, 'null' => true, 'after' => 'pos_profile_id']);
        $add('tax_template_id', ['type' => 'INT', 'unsigned' => true, 'null' => true, 'after' => 'pos_shift_id']);
        $add('tax_total', ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0, 'after' => 'discount_total']);
        $add('rounding_adjustment', ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0, 'after' => 'tax_total']);
        $add('coupon_code', ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true, 'after' => 'applied_price_list_id']);
        $add('coupon_discount', ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0, 'after' => 'coupon_code']);
        $add('loyalty_points_redeemed', ['type' => 'INT', 'default' => 0, 'after' => 'coupon_discount']);
        $add('loyalty_discount', ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0, 'after' => 'loyalty_points_redeemed']);
        $add('loyalty_points_earned', ['type' => 'INT', 'default' => 0, 'after' => 'loyalty_discount']);

        if (! empty($fields)) {
            $this->forge->addColumn('orders', $fields);
        }

        $this->alignOrderColumnTypes();
    }

    private function normalizePaymentCodes(): void
    {
        $map = [
            'cash' => 'CASH',
            'CASH' => 'CASH',
            'bank_transfer' => 'BANK_TRANSFER',
            'BANK_TRANSFER' => 'BANK_TRANSFER',
            'card' => 'CARD',
            'CARD' => 'CARD',
            'cod' => 'COD',
            'COD' => 'COD',
            'ewallet' => 'EWALLET',
            'EWALLET' => 'EWALLET',
            'e_wallet' => 'EWALLET',
            'E_WALLET' => 'EWALLET',
        ];

        $tables = ['orders', 'order_payments', 'cash_transactions'];
        foreach ($map as $from => $to) {
            foreach ($tables as $table) {
                if ($this->db->tableExists($table) && $this->db->fieldExists('payment_method', $table)) {
                    $this->db->table($table)->where('payment_method', $from)->set('payment_method', $to)->update();
                }
            }
        }

        if ($this->db->tableExists('payment_methods')) {
            foreach ($map as $from => $to) {
                $this->db->table('payment_methods')->where('code', $from)->set('code', $to)->update();
            }

            $now = date('Y-m-d H:i:s');
            $defaults = [
                ['code' => 'CASH', 'name' => 'Tiền mặt', 'description' => 'Thanh toán tiền mặt', 'is_active' => 1, 'display_order' => 1],
                ['code' => 'BANK_TRANSFER', 'name' => 'Chuyển khoản', 'description' => 'Chuyển khoản ngân hàng', 'is_active' => 1, 'display_order' => 2],
                ['code' => 'CARD', 'name' => 'Thẻ', 'description' => 'Thẻ tín dụng/ghi nợ', 'is_active' => 1, 'display_order' => 3],
                ['code' => 'COD', 'name' => 'Thu hộ (COD)', 'description' => 'Thanh toán khi nhận hàng', 'is_active' => 1, 'display_order' => 4],
                ['code' => 'EWALLET', 'name' => 'Ví điện tử', 'description' => 'MoMo/ZaloPay/VNPay', 'is_active' => 1, 'display_order' => 5],
            ];

            foreach ($defaults as &$row) {
                $row['created_at'] = $row['updated_at'] = $now;
            }
            unset($row);

            $this->db->table('payment_methods')->ignore(true)->insertBatch($defaults);
        }
    }

    private function syncOrderPaymentsSchema(): void
    {
        if (! $this->db->tableExists('order_payments')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
                'order_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => false],
                'payment_method' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
                'amount' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'null' => false],
                'paid_at' => ['type' => 'DATETIME', 'null' => true],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
                'updated_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('order_id');
            $this->forge->createTable('order_payments', true);
        } else {
            if ($this->db->fieldExists('method', 'order_payments') && ! $this->db->fieldExists('payment_method', 'order_payments')) {
                $this->forge->modifyColumn('order_payments', [
                    'method' => [
                        'name' => 'payment_method',
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                        'null' => true,
                    ],
                ]);
            }

            if ($this->db->fieldExists('payment_method', 'order_payments')) {
                $this->cleanupInvalidMethods();
                $this->forcePaymentMethodVarchar();
            }

            if (! $this->db->fieldExists('created_at', 'order_payments')) {
                $this->forge->addColumn('order_payments', ['created_at' => ['type' => 'DATETIME', 'null' => true]]);
            }
            if (! $this->db->fieldExists('updated_at', 'order_payments')) {
                $this->forge->addColumn('order_payments', ['updated_at' => ['type' => 'DATETIME', 'null' => true]]);
            }
        }

        $this->cleanupOrphans('order_payments', 'order_id', 'orders');
        $this->cleanupInvalidMethods();

        if (! $this->foreignKeyExists('order_payments', 'fk_order_payments_order')) {
            $this->forge->addColumn('order_payments', [
                'CONSTRAINT fk_order_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE',
            ]);
        }

        if (! $this->foreignKeyExists('order_payments', 'fk_order_payments_method')) {
            $this->alignPaymentMethodCollation();
            $this->forge->addColumn('order_payments', [
                'CONSTRAINT fk_order_payments_method FOREIGN KEY (payment_method) REFERENCES payment_methods(code) ON DELETE SET NULL ON UPDATE CASCADE',
            ]);
        }
    }

    private function forcePaymentMethodVarchar(): void
    {
        if (! $this->db->fieldExists('payment_method', 'order_payments')) {
            return;
        }

        $collation = $this->resolveUtf8mb4Collation();

        // Drop FK first to allow type change
        $this->dropFkIfExists('order_payments', 'fk_order_payments_method');

        // If already varchar, this is idempotent
        $this->db->query("ALTER TABLE order_payments MODIFY payment_method VARCHAR(50) CHARACTER SET utf8mb4 COLLATE {$collation} NULL");
    }

    private function alignPaymentMethodCollation(): void
    {
        // Ensure collations match for FK compatibility
        $collation = $this->resolveUtf8mb4Collation();

        if ($this->db->tableExists('payment_methods') && $this->db->fieldExists('code', 'payment_methods')) {
            $this->db->query("ALTER TABLE payment_methods MODIFY code VARCHAR(50) CHARACTER SET utf8mb4 COLLATE {$collation} NULL");
        }
        if ($this->db->tableExists('order_payments') && $this->db->fieldExists('payment_method', 'order_payments')) {
            $this->db->query("ALTER TABLE order_payments MODIFY payment_method VARCHAR(50) CHARACTER SET utf8mb4 COLLATE {$collation} NULL");
        }
    }

    private function alignOrderColumnTypes(): void
    {
        // Align types with referenced tables for FK compatibility
        $collation = $this->resolveUtf8mb4Collation();
        $this->db->query('ALTER TABLE orders MODIFY customer_id BIGINT UNSIGNED NULL');
        $this->db->query('ALTER TABLE orders MODIFY branch_id BIGINT UNSIGNED NULL');
        $this->db->query("ALTER TABLE orders MODIFY payment_method VARCHAR(50) CHARACTER SET utf8mb4 COLLATE {$collation} NULL");
        if ($this->db->tableExists('returns') && $this->db->fieldExists('customer_id', 'returns')) {
            $this->db->query('ALTER TABLE returns MODIFY customer_id BIGINT UNSIGNED NULL');
        }
    }

    private function resolveUtf8mb4Collation(): string
    {
        $version = strtolower((string) $this->db->getVersion());
        $numeric = preg_replace('/[^0-9.]/', '', $version);

        // MariaDB or older MySQL fallback to cross-compatible collation
        if (str_contains($version, 'mariadb')) {
            return 'utf8mb4_unicode_520_ci';
        }

        if ($numeric !== '' && version_compare($numeric, '8.0', '>=')) {
            return 'utf8mb4_0900_ai_ci';
        }

        return 'utf8mb4_unicode_520_ci';
    }

    private function addOrderForeignKeys(): void
    {
        if (! $this->db->tableExists('orders')) {
            return;
        }

        if ($this->db->tableExists('customers')) {
            $this->nullifyMissingRefs('orders', 'customer_id', 'customers');
            if (! $this->foreignKeyExists('orders', 'fk_orders_customer')) {
                $this->forge->addColumn('orders', [
                    'CONSTRAINT fk_orders_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL ON UPDATE CASCADE',
                ]);
            }
        }

        if ($this->db->tableExists('branches')) {
            $this->nullifyMissingRefs('orders', 'branch_id', 'branches');
            if (! $this->foreignKeyExists('orders', 'fk_orders_branch')) {
                $this->forge->addColumn('orders', [
                    'CONSTRAINT fk_orders_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL ON UPDATE CASCADE',
                ]);
            }
        }

        if ($this->db->tableExists('payment_methods')) {
            $this->cleanupInvalidMethods();
            if (! $this->foreignKeyExists('orders', 'fk_orders_payment_method')) {
                $this->forge->addColumn('orders', [
                    'CONSTRAINT fk_orders_payment_method FOREIGN KEY (payment_method) REFERENCES payment_methods(code) ON DELETE SET NULL ON UPDATE CASCADE',
                ]);
            }
        }
    }

    private function addReturnForeignKeys(): void
    {
        if (! $this->db->tableExists('returns')) {
            return;
        }

        if ($this->db->tableExists('orders')) {
            $this->cleanupOrphans('returns', 'order_id', 'orders');
            if (! $this->foreignKeyExists('returns', 'fk_returns_order')) {
                $this->forge->addColumn('returns', [
                    'CONSTRAINT fk_returns_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE',
                ]);
            }
        }

        if ($this->db->tableExists('customers')) {
            $this->nullifyMissingRefs('returns', 'customer_id', 'customers');
            if (! $this->foreignKeyExists('returns', 'fk_returns_customer')) {
                $this->forge->addColumn('returns', [
                    'CONSTRAINT fk_returns_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL ON UPDATE CASCADE',
                ]);
            }
        }
    }

    private function addDeliveryForeignKeys(): void
    {
        if (! $this->db->tableExists('delivery_notes')) {
            return;
        }

        if ($this->db->tableExists('orders')) {
            $this->cleanupOrphans('delivery_notes', 'order_id', 'orders');
            if (! $this->foreignKeyExists('delivery_notes', 'fk_delivery_notes_order')) {
                $this->forge->addColumn('delivery_notes', [
                    'CONSTRAINT fk_delivery_notes_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE ON UPDATE CASCADE',
                ]);
            }
        }

        if ($this->db->tableExists('customers')) {
            $this->nullifyMissingRefs('delivery_notes', 'customer_id', 'customers');
            if (! $this->foreignKeyExists('delivery_notes', 'fk_delivery_notes_customer')) {
                $this->forge->addColumn('delivery_notes', [
                    'CONSTRAINT fk_delivery_notes_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL ON UPDATE CASCADE',
                ]);
            }
        }

        if ($this->db->tableExists('branches')) {
            $this->nullifyMissingRefs('delivery_notes', 'branch_id', 'branches');
            if (! $this->foreignKeyExists('delivery_notes', 'fk_delivery_notes_branch')) {
                $this->forge->addColumn('delivery_notes', [
                    'CONSTRAINT fk_delivery_notes_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL ON UPDATE CASCADE',
                ]);
            }
        }

        if ($this->db->tableExists('delivery_note_items')) {
            if ($this->db->tableExists('delivery_notes')) {
                $this->cleanupOrphans('delivery_note_items', 'delivery_note_id', 'delivery_notes');
                if (! $this->foreignKeyExists('delivery_note_items', 'fk_delivery_note_items_note')) {
                    $this->forge->addColumn('delivery_note_items', [
                        'CONSTRAINT fk_delivery_note_items_note FOREIGN KEY (delivery_note_id) REFERENCES delivery_notes(id) ON DELETE CASCADE ON UPDATE CASCADE',
                    ]);
                }
            }

            if ($this->db->tableExists('order_items')) {
                $this->cleanupOrphans('delivery_note_items', 'order_item_id', 'order_items');
                if (! $this->foreignKeyExists('delivery_note_items', 'fk_delivery_note_items_order_item')) {
                    $this->forge->addColumn('delivery_note_items', [
                        'CONSTRAINT fk_delivery_note_items_order_item FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE SET NULL ON UPDATE CASCADE',
                    ]);
                }
            }
        }
    }

    private function addCashTransactionForeignKeys(): void
    {
        if (! $this->db->tableExists('cash_transactions')) {
            return;
        }

        $this->cleanupOrphans('cash_transactions', 'branch_id', 'branches');
        $this->cleanupOrphans('cash_transactions', 'created_by', 'users');

        if (! $this->foreignKeyExists('cash_transactions', 'fk_cash_transactions_branch')) {
            $this->forge->addColumn('cash_transactions', [
                'CONSTRAINT fk_cash_transactions_branch FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE CASCADE ON UPDATE CASCADE',
            ]);
        }
        if (! $this->foreignKeyExists('cash_transactions', 'fk_cash_transactions_user')) {
            $this->forge->addColumn('cash_transactions', [
                'CONSTRAINT fk_cash_transactions_user FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE',
            ]);
        }
    }

    private function cleanupOrphans(string $table, string $column, string $refTable, string $refColumn = 'id'): void
    {
        if (! $this->db->tableExists($table) || ! $this->db->tableExists($refTable)) {
            return;
        }

        $existing = array_map(
            static fn ($row) => (int) $row[$refColumn],
            $this->db->table($refTable)->select($refColumn)->get()->getResultArray()
        );

        if (empty($existing)) {
            $this->db->table($table)->truncate();
            return;
        }

        $rows = $this->db->table($table)->select("id, {$column}")->get()->getResultArray();
        $deleteIds = [];
        foreach ($rows as $row) {
            $refId = (int) $row[$column];
            if ($refId && ! in_array($refId, $existing, true)) {
                $deleteIds[] = (int) $row['id'];
            }
        }

        if (! empty($deleteIds)) {
            $this->db->table($table)->whereIn('id', $deleteIds)->delete();
        }
    }

    private function nullifyMissingRefs(string $table, string $column, string $refTable, string $refColumn = 'id'): void
    {
        if (! $this->db->tableExists($table) || ! $this->db->tableExists($refTable)) {
            return;
        }

        $existing = array_map(
            static fn ($row) => (int) $row[$refColumn],
            $this->db->table($refTable)->select($refColumn)->get()->getResultArray()
        );

        if (empty($existing)) {
            $this->db->table($table)->set($column, null)->update();
            return;
        }

        $this->db->table($table)->whereNotIn($column, $existing)->set($column, null)->update();
    }

    private function cleanupInvalidMethods(): void
    {
        if (! $this->db->tableExists('payment_methods')) {
            return;
        }

        $valid = array_column($this->db->table('payment_methods')->select('code')->get()->getResultArray(), 'code');
        if (empty($valid)) {
            return;
        }

        foreach (['orders', 'order_payments'] as $table) {
            if (! $this->db->tableExists($table) || ! $this->db->fieldExists('payment_method', $table)) {
                continue;
            }
            $this->db->table($table)->whereNotIn('payment_method', $valid)->set('payment_method', null)->update();
        }
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $sql = 'SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?';
        $dbName = $this->db->getDatabase();
        $row = $this->db->query($sql, [$dbName, $table, $constraint])->getRowArray();

        return ! empty($row);
    }

    private function dropFkIfExists(string $table, string $constraint): void
    {
        if ($this->foreignKeyExists($table, $constraint)) {
            $this->forge->dropForeignKey($table, $constraint);
        }
    }

    private function isSqlite(): bool
    {
        return strtolower($this->db->DBDriver) === 'sqlite3';
    }
}
