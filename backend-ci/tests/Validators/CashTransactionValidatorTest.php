<?php
namespace Tests\Validators;

use App\Validators\CashTransactionValidator;
use App\Validators\CashTransactionReferenceValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CashTransactionSchemaTrait;

/**
 * @agent-test: CashTransactionValidator unit testing
 * @agent-pattern: Validator test with DevDatabaseTrait + SchemaTrait (MySQL-only)
 */
class CashTransactionValidatorTest extends CIUnitTestCase
{
    use DevDatabaseTrait;              // MySQL connection + transactions
    use CashTransactionSchemaTrait;    // Schema creation for cash transactions

    private CashTransactionValidator $validator;
    private CashTransactionReferenceValidator $referenceValidator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();          // Connect to MySQL + start transaction
        
        // Truncate tables to ensure clean state
        $this->resetCashTransactionSchema();
        
        $this->validator = new CashTransactionValidator(null, $this->db);
        $this->referenceValidator = new CashTransactionReferenceValidator($this->db);
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();      // Rollback transaction
        parent::tearDown();
    }

    /** @test */
    public function it_validates_receipt_with_valid_data()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch', 'status' => 'active']);
        $userId = $this->seedUser(['username' => 'testuser']);
        
        // Debug: Check if data was seeded
        $this->assertGreaterThan(0, $branchId, "Branch ID should be > 0, got: $branchId");
        $this->assertGreaterThan(0, $userId, "User ID should be > 0, got: $userId");
        
        $data = [
            'amount' => '500000.00', // String format for validation
            'category' => 'sales',
            'description' => 'Bán hàng HD001',
            'branch_id' => (string) $branchId, // String format for validation
            'created_by' => (string) $userId,   // String format for validation
            'transaction_date' => '2025-11-26',
            'reference_type' => 'manual'
        ];

        // Act
        $result = $this->validator->validateReceipt($data);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(500000, $result['amount']);
        $this->assertEquals('sales', $result['category']);
        $this->assertEquals($branchId, $result['branch_id']);
        $this->assertEquals('RECEIPT', $result['type']);
    }

    /** @test */
    public function it_validates_payment_with_valid_data()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch', 'status' => 'active']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $data = [
            'amount' => '200000.00', // String format for validation
            'category' => 'expense',
            'description' => 'Chi phí văn phòng',
            'branch_id' => (string) $branchId, // String format for validation
            'created_by' => (string) $userId,   // String format for validation
            'transaction_date' => '2025-11-26',
            'reference_type' => 'manual'
        ];

        // Act
        $result = $this->validator->validatePayment($data);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals(200000, $result['amount']);
        $this->assertEquals('expense', $result['category']);
        $this->assertEquals($branchId, $result['branch_id']);
        $this->assertEquals('PAYMENT', $result['type']);
    }

    /** @test */
    public function it_throws_on_invalid_amount_for_receipt()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch', 'status' => 'active']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $data = [
            'amount' => '-1000', // Negative amount as string
            'category' => 'sales',
            'branch_id' => (string) $branchId,
            'created_by' => (string) $userId,
            'transaction_date' => '2025-11-26'
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('greater than 0');
        
        // Act
        $this->validator->validateReceipt($data);
    }

    /** @test */
    public function it_throws_on_invalid_amount_for_payment()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch', 'status' => 'active']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $data = [
            'amount' => '0', // Zero amount as string
            'category' => 'expense',
            'branch_id' => (string) $branchId,
            'created_by' => (string) $userId,
            'transaction_date' => '2025-11-26'
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('greater than 0');
        
        // Act
        $this->validator->validatePayment($data);
    }

    /** @test */
    public function it_throws_on_invalid_receipt_category()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch', 'status' => 'active']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $data = [
            'amount' => '100000.00',
            'category' => 'invalid_category', // Invalid category
            'branch_id' => (string) $branchId,
            'created_by' => (string) $userId,
            'transaction_date' => '2025-11-26'
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid category for RECEIPT');
        
        // Act
        $this->validator->validateReceipt($data);
    }

    /** @test */
    public function it_throws_on_invalid_payment_category()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch', 'status' => 'active']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $data = [
            'amount' => '100000.00',
            'category' => 'sales', // Invalid category for payment
            'branch_id' => (string) $branchId,
            'created_by' => (string) $userId,
            'transaction_date' => '2025-11-26'
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid category for PAYMENT');
        
        // Act
        $this->validator->validatePayment($data);
    }

    /** @test */
    public function it_throws_on_future_transaction_date()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch', 'status' => 'active']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $futureDate = date('Y-m-d', strtotime('+1 day'));
        $data = [
            'amount' => '100000.00',
            'category' => 'sales',
            'branch_id' => (string) $branchId,
            'created_by' => (string) $userId,
            'transaction_date' => $futureDate // Future date
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction date cannot be in the future');
        
        // Act
        $this->validator->validateReceipt($data);
    }

    /** @test */
    public function it_throws_on_invalid_branch_id()
    {
        // Arrange
        $userId = $this->seedUser(['username' => 'testuser']);
        $data = [
            'amount' => '100000.00',
            'category' => 'sales',
            'branch_id' => '99999', // Non-existent branch as string
            'created_by' => (string) $userId,
            'transaction_date' => '2025-11-26'
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Branch not found or inactive');
        
        // Act
        $this->validator->validateReceipt($data);
    }

    /** @test */
    public function it_throws_on_inactive_branch()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Inactive Branch', 'status' => 'inactive']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $data = [
            'amount' => '100000.00',
            'category' => 'sales',
            'branch_id' => (string) $branchId,
            'created_by' => (string) $userId,
            'transaction_date' => '2025-11-26'
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Branch not found or inactive');
        
        // Act
        $this->validator->validateReceipt($data);
    }

    /** @test */
    public function it_validates_order_reference_successfully()
    {
        // Arrange
        $orderId = $this->seedOrder(['code' => 'HD001', 'total' => 500000]);

        // Act
        $result = $this->referenceValidator->validateReference('order', $orderId, 500000);

        // Assert
        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function it_validates_purchase_order_reference_successfully()
    {
        // Arrange
        $purchaseOrderId = $this->seedPurchaseOrder(['code' => 'PO001', 'total' => 300000]);

        // Act
        $result = $this->referenceValidator->validateReference('purchase_order', $purchaseOrderId, 300000);

        // Assert
        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function it_validates_manual_reference_successfully()
    {
        // Act
        $result = $this->referenceValidator->validateReference('manual', 0, 0);

        // Assert
        $this->assertTrue($result['valid']);
    }

    /** @test */
    public function it_throws_on_nonexistent_order_reference()
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order #99999 not found');
        
        // Act
        $this->referenceValidator->validateReference('order', 99999, 100000);
    }

    /** @test */
    public function it_throws_on_nonexistent_purchase_order_reference()
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Purchase order #99999 not found');
        
        // Act
        $this->referenceValidator->validateReference('purchase_order', 99999, 100000);
    }

    /** @test */
    public function it_throws_on_invalid_reference_type()
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid reference type');
        
        // Act
        $this->referenceValidator->validateReference('invalid_type', 123, 100000);
    }

    /** @test */
    public function it_accepts_all_valid_receipt_categories()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch', 'status' => 'active']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $validCategories = ['sales', 'refund', 'deposit', 'other_income'];

        foreach ($validCategories as $category) {
            $data = [
                'amount' => '100000.00',
                'category' => $category,
                'branch_id' => (string) $branchId,
                'created_by' => (string) $userId,
                'transaction_date' => '2025-11-26'
            ];

            // Act
            $result = $this->validator->validateReceipt($data);

            // Assert
            $this->assertEquals($category, $result['category']);
        }
    }

    /** @test */
    public function it_accepts_all_valid_payment_categories()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch', 'status' => 'active']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $validCategories = ['purchase', 'salary', 'expense', 'withdrawal', 'other_expense'];

        foreach ($validCategories as $category) {
            $data = [
                'amount' => '100000.00',
                'category' => $category,
                'branch_id' => (string) $branchId,
                'created_by' => (string) $userId,
                'transaction_date' => '2025-11-26'
            ];

            // Act
            $result = $this->validator->validatePayment($data);

            // Assert
            $this->assertEquals($category, $result['category']);
        }
    }

    // Helper methods for MySQL
    private function seedBranch(array $data): int
    {
        $payload = array_merge([
            'name' => 'Test Branch',
            'code' => 'BR' . random_int(1000, 9999),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('branches')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedOrder(array $data): int
    {
        $payload = array_merge([
            'order_number' => 'HD' . random_int(1000, 9999),
            'code' => 'HD' . random_int(1000, 9999),
            'total' => 0,
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('orders')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedUser(array $data): int
    {
        // Always generate unique username/email to avoid UNIQUE constraint violations
        $uniqueId = random_int(100000, 999999);
        $payload = array_merge([
            'username' => 'user' . $uniqueId,
            'email' => 'user' . $uniqueId . '@test.com',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);
        
        // Override username if provided to make it unique
        if (isset($data['username'])) {
            $payload['username'] = $data['username'] . '_' . $uniqueId;
        }

        $this->db->table('users')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedPurchaseOrder(array $data): int
    {
        $payload = array_merge([
            'po_number' => 'PO' . random_int(1000, 9999),
            'code' => 'PO' . random_int(1000, 9999),
            'total' => 0,
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('purchase_orders')->insert($payload);
        return (int) $this->db->insertID();
    }
}
