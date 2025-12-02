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
        $dbName = $this->db->database ?? $this->db->getDatabase();
        $row = $this->db->query(
            "SELECT CONSTRAINT_NAME FROM information_schema.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?",
            [$dbName, $table, $constraint]
        )->getRowArray();

        return ! empty($row);
    }
}
