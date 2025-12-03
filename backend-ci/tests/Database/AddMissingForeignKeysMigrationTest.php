<?php

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Migration FK coverage
 * @agent-pattern: Validate foreign keys on demo link tables
 */
class AddMissingForeignKeysMigrationTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function invoice_orders_has_foreign_keys(): void
    {
        $this->assertTrue($this->foreignKeyExists('invoice_orders', 'fk_invoice_orders_invoice'));
        $this->assertTrue($this->foreignKeyExists('invoice_orders', 'fk_invoice_orders_order'));
    }

    /** @test */
    public function return_items_has_foreign_keys(): void
    {
        $this->assertTrue($this->foreignKeyExists('return_items', 'fk_return_items_return'));
        $this->assertTrue($this->foreignKeyExists('return_items', 'fk_return_items_order_item'));
    }

    /** @test */
    public function orders_and_payments_have_foreign_keys(): void
    {
        $this->assertTrue($this->foreignKeyExists('orders', 'fk_orders_customer'));
        $this->assertTrue($this->foreignKeyExists('orders', 'fk_orders_branch'));
        $this->assertTrue($this->foreignKeyExists('orders', 'fk_orders_payment_method'));
        $this->assertTrue($this->foreignKeyExists('order_payments', 'fk_order_payments_order'));
        $this->assertTrue($this->foreignKeyExists('order_payments', 'fk_order_payments_method'));
    }

    /** @test */
    public function deliveries_and_cash_have_foreign_keys(): void
    {
        $this->assertTrue($this->foreignKeyExists('delivery_notes', 'fk_delivery_notes_order'));
        $this->assertTrue($this->foreignKeyExists('delivery_notes', 'fk_delivery_notes_customer'));
        $this->assertTrue($this->foreignKeyExists('delivery_notes', 'fk_delivery_notes_branch'));
        $this->assertTrue($this->foreignKeyExists('delivery_note_items', 'fk_delivery_note_items_note'));
        $this->assertTrue($this->foreignKeyExists('delivery_note_items', 'fk_delivery_note_items_order_item'));
        $this->assertTrue($this->foreignKeyExists('cash_transactions', 'fk_cash_transactions_branch'));
        $this->assertTrue($this->foreignKeyExists('cash_transactions', 'fk_cash_transactions_user'));
    }

    /** @test */
    public function returns_have_foreign_keys(): void
    {
        $this->assertTrue($this->foreignKeyExists('returns', 'fk_returns_order'));
        $this->assertTrue($this->foreignKeyExists('returns', 'fk_returns_customer'));
    }

    private function foreignKeyExists(string $table, string $constraint): bool
    {
        $result = $this->db->query("SHOW CREATE TABLE `{$table}`");
        if ($result === false) {
            error_log("DEBUG: SHOW CREATE failed for {$table} - " . json_encode($this->db->error()));
            return false;
        }
        $row = $result->getRowArray();
        $ddl = $row['Create Table'] ?? '';
        $exists = $ddl && strpos($ddl, $constraint) !== false;
        error_log("DEBUG: FK check {$constraint} on {$table} => " . ($exists ? '1' : '0'));
        return $exists;
    }
}
