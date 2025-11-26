<?php
namespace Tests\Services;

use App\Services\CashTransactions\CashTransactionService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CashTransactionSchemaTrait;

/**
 * @agent-test: CashTransactionService unit testing
 * @agent-pattern: Service test with DevDatabaseTrait + SchemaTrait (MySQL-only)
 */
class CashTransactionServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;              // MySQL connection + transactions
    use CashTransactionSchemaTrait;    // Schema creation for cash transactions

    private CashTransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();          // Connect to MySQL + start transaction
        
        // Create cash transaction schema
        $this->resetCashTransactionSchema();     // Creates cash_transactions table in MySQL
        
        // Create supporting tables
        $this->createSupportingTables();
        
        $this->service = new CashTransactionService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();       // Rollback transaction + close connection
        parent::tearDown();
    }

    /** @test */
    public function it_creates_receipt_with_valid_data()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $data = [
            'amount' => 500000,
            'category' => 'sales',
            'description' => 'Bán hàng HD001',
            'branch_id' => $branchId,
            'created_by' => $userId,
            'transaction_date' => '2025-11-26',
            'reference_type' => 'manual'
        ];

        // Act
        $result = $this->service->createReceipt($data);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('RECEIPT', $result['data']['type']);
        $this->assertEquals(500000, $result['data']['amount']);
        $this->assertEquals('sales', $result['data']['category']);
        $this->assertEquals($branchId, $result['data']['branch_id']);
        
        // Verify in MySQL database
        $row = $this->db->table('cash_transactions')
            ->where('id', $result['data']['id'])
            ->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertEquals('RECEIPT', $row['type']);
    }

    /** @test */
    public function it_creates_payment_with_valid_data()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $data = [
            'amount' => 200000,
            'category' => 'expense',
            'description' => 'Chi phí văn phòng',
            'branch_id' => $branchId,
            'created_by' => $userId,
            'transaction_date' => '2025-11-26',
            'reference_type' => 'manual'
        ];

        // Act
        $result = $this->service->createPayment($data);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('PAYMENT', $result['data']['type']);
        $this->assertEquals(200000, $result['data']['amount']);
        $this->assertEquals('expense', $result['data']['category']);
    }

    /** @test */
    public function it_creates_receipt_with_order_reference()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $orderId = $this->seedOrder(['code' => 'HD001', 'total' => 500000]);
        $data = [
            'amount' => 500000,
            'category' => 'sales',
            'description' => 'Thanh toán đơn hàng HD001',
            'branch_id' => $branchId,
            'created_by' => $userId,
            'transaction_date' => '2025-11-26',
            'reference_type' => 'order',
            'reference_id' => $orderId,
            'reference_code' => 'HD001'
        ];

        // Act
        $result = $this->service->createReceipt($data);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('order', $result['data']['reference_type']);
        $this->assertEquals($orderId, $result['data']['reference_id']);
        $this->assertEquals('HD001', $result['data']['reference_code']);
    }

    /** @test */
    public function it_creates_payment_with_purchase_order_reference()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $purchaseOrderId = $this->seedPurchaseOrder(['code' => 'PO001', 'total' => 300000]);
        $data = [
            'amount' => 300000,
            'category' => 'purchase',
            'description' => 'Thanh toán PO001',
            'branch_id' => $branchId,
            'created_by' => $userId,
            'transaction_date' => '2025-11-26',
            'reference_type' => 'purchase_order',
            'reference_id' => $purchaseOrderId,
            'reference_code' => 'PO001'
        ];

        // Act
        $result = $this->service->createPayment($data);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals('purchase_order', $result['data']['reference_type']);
        $this->assertEquals($purchaseOrderId, $result['data']['reference_id']);
        $this->assertEquals('PO001', $result['data']['reference_code']);
    }

    /** @test */
    public function it_throws_on_invalid_receipt_data()
    {
        // Arrange
        $data = [
            'amount' => -1000, // Invalid amount
            'category' => 'sales',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => '2025-11-26'
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        
        // Act
        $this->service->createReceipt($data);
    }

    /** @test */
    public function it_throws_on_invalid_payment_data()
    {
        // Arrange
        $data = [
            'amount' => 0, // Invalid amount
            'category' => 'expense',
            'branch_id' => 1,
            'created_by' => 1,
            'transaction_date' => '2025-11-26'
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        
        // Act
        $this->service->createPayment($data);
    }

    /** @test */
    public function it_throws_on_nonexistent_order_reference()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $data = [
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => $branchId,
            'created_by' => $userId,
            'transaction_date' => '2025-11-26',
            'reference_type' => 'order',
            'reference_id' => 99999 // Non-existent order
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Order #99999 not found');
        
        // Act
        $this->service->createReceipt($data);
    }

    /** @test */
    public function it_throws_on_amount_mismatch_with_order_total()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $orderId = $this->seedOrder(['code' => 'HD001', 'total' => 500000]);
        $data = [
            'amount' => 400000, // Different from order total
            'category' => 'sales',
            'branch_id' => $branchId,
            'created_by' => $userId,
            'transaction_date' => '2025-11-26',
            'reference_type' => 'order',
            'reference_id' => $orderId,
            'reference_code' => 'HD001'
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Amount does not match order total');
        
        // Act
        $this->service->createReceipt($data);
    }

    /** @test */
    public function it_throws_on_duplicate_payment_for_purchase_order()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $purchaseOrderId = $this->seedPurchaseOrder(['code' => 'PO001', 'total' => 300000]);
        
        // Create first payment
        $this->seedCashTransaction([
            'type' => 'PAYMENT',
            'amount' => 300000,
            'reference_type' => 'purchase_order',
            'reference_id' => $purchaseOrderId,
            'branch_id' => $branchId
        ]);

        $data = [
            'amount' => 300000,
            'category' => 'purchase',
            'branch_id' => $branchId,
            'created_by' => $userId,
            'transaction_date' => '2025-11-26',
            'reference_type' => 'purchase_order',
            'reference_id' => $purchaseOrderId,
            'reference_code' => 'PO001'
        ];

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Purchase order already paid');
        
        // Act
        $this->service->createPayment($data);
    }

    /** @test */
    public function it_gets_transaction_by_id()
    {
        // Arrange
        $transactionId = $this->seedCashTransaction([
            'type' => 'RECEIPT',
            'amount' => 500000,
            'category' => 'sales'
        ]);

        // Act
        $result = $this->service->getTransaction($transactionId);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals($transactionId, $result['id']);
        $this->assertEquals('RECEIPT', $result['type']);
        $this->assertEquals(500000, $result['amount']);
    }

    /** @test */
    public function it_throws_on_nonexistent_transaction()
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction not found');
        
        // Act
        $this->service->getTransaction(99999);
    }

    /** @test */
    public function it_lists_transactions_with_filters()
    {
        // Arrange
        $branchId1 = $this->seedBranch(['name' => 'Branch 1']);
        $branchId2 = $this->seedBranch(['name' => 'Branch 2']);
        
        $this->seedCashTransaction(['type' => 'RECEIPT', 'amount' => 100000, 'category' => 'sales', 'branch_id' => $branchId1]);
        $this->seedCashTransaction(['type' => 'PAYMENT', 'amount' => 50000, 'category' => 'expense', 'branch_id' => $branchId1]);
        $this->seedCashTransaction(['type' => 'RECEIPT', 'amount' => 200000, 'category' => 'sales', 'branch_id' => $branchId2]);

        // Act
        $result = $this->service->listTransactions(['type' => 'RECEIPT'], 1, 10);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['data']);
        
        // Verify all results are RECEIPT type
        foreach ($result['data'] as $transaction) {
            $this->assertEquals('RECEIPT', $transaction['type']);
        }
    }

    /** @test */
    public function it_gets_balance_for_all_branches()
    {
        // Arrange
        $this->seedCashTransaction(['type' => 'RECEIPT', 'amount' => 500000, 'category' => 'sales']);
        $this->seedCashTransaction(['type' => 'RECEIPT', 'amount' => 300000, 'category' => 'refund']);
        $this->seedCashTransaction(['type' => 'PAYMENT', 'amount' => 200000, 'category' => 'expense']);
        $this->seedCashTransaction(['type' => 'PAYMENT', 'amount' => 100000, 'category' => 'purchase']);

        // Act
        $result = $this->service->getBalance();

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(500000, $result['data']['balance']); // (500k + 300k) - (200k + 100k) = 500k
        $this->assertNull($result['data']['branch_id']); // All branches
        $this->assertArrayHasKey('as_of', $result['data']);
    }

    /** @test */
    public function it_gets_balance_for_specific_branch()
    {
        // Arrange
        $branchId1 = $this->seedBranch(['name' => 'Branch 1']);
        $branchId2 = $this->seedBranch(['name' => 'Branch 2']);
        
        $this->seedCashTransaction(['type' => 'RECEIPT', 'amount' => 300000, 'branch_id' => $branchId1]);
        $this->seedCashTransaction(['type' => 'PAYMENT', 'amount' => 100000, 'branch_id' => $branchId1]);
        $this->seedCashTransaction(['type' => 'RECEIPT', 'amount' => 500000, 'branch_id' => $branchId2]);

        // Act
        $result = $this->service->getBalance($branchId1);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(200000, $result['data']['balance']); // 300k - 100k = 200k
        $this->assertEquals($branchId1, $result['data']['branch_id']);
    }

    /** @test */
    public function it_gets_daily_report()
    {
        // Arrange
        $this->seedCashTransaction([
            'type' => 'RECEIPT',
            'amount' => 300000,
            'category' => 'sales',
            'transaction_date' => '2025-11-26'
        ]);
        $this->seedCashTransaction([
            'type' => 'RECEIPT',
            'amount' => 200000,
            'category' => 'refund',
            'transaction_date' => '2025-11-26'
        ]);
        $this->seedCashTransaction([
            'type' => 'PAYMENT',
            'amount' => 150000,
            'category' => 'expense',
            'transaction_date' => '2025-11-26'
        ]);
        $this->seedCashTransaction([
            'type' => 'PAYMENT',
            'amount' => 50000,
            'category' => 'purchase',
            'transaction_date' => '2025-11-26'
        ]);

        // Act
        $result = $this->service->getDailyReport('2025-11-26');

        // Assert
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(500000, $result['data']['receipt_total']); // 300k + 200k
        $this->assertEquals(200000, $result['data']['payment_total']); // 150k + 50k
        $this->assertEquals(300000, $result['data']['balance']); // 500k - 200k
        $this->assertEquals(300000, $result['data']['net']); // Same as balance
    }

    /** @test */
    public function it_gets_daily_report_for_specific_branch()
    {
        // Arrange
        $branchId1 = $this->seedBranch(['name' => 'Branch 1']);
        $branchId2 = $this->seedBranch(['name' => 'Branch 2']);
        
        $this->seedCashTransaction([
            'type' => 'RECEIPT',
            'amount' => 300000,
            'branch_id' => $branchId1,
            'transaction_date' => '2025-11-26'
        ]);
        $this->seedCashTransaction([
            'type' => 'PAYMENT',
            'amount' => 100000,
            'branch_id' => $branchId1,
            'transaction_date' => '2025-11-26'
        ]);
        $this->seedCashTransaction([
            'type' => 'RECEIPT',
            'amount' => 500000,
            'branch_id' => $branchId2,
            'transaction_date' => '2025-11-26'
        ]);

        // Act
        $result = $this->service->getDailyReport('2025-11-26', $branchId1);

        // Assert
        $this->assertTrue($result['success']);
        $this->assertEquals(300000, $result['data']['receipt_total']);
        $this->assertEquals(100000, $result['data']['payment_total']);
        $this->assertEquals(200000, $result['data']['balance']);
        $this->assertEquals(200000, $result['data']['net']);
    }

    /** @test */
    public function it_deletes_transaction()
    {
        // Arrange
        $transactionId = $this->seedCashTransaction([
            'type' => 'RECEIPT',
            'amount' => 100000,
            'category' => 'sales',
            'transaction_date' => date('Y-m-d') // Recent transaction
        ]);

        // Verify transaction exists
        $before = $this->service->getTransaction($transactionId);
        $this->assertNotNull($before);

        // Act
        $result = $this->service->deleteTransaction($transactionId);

        // Assert
        $this->assertTrue($result['success']);
        
        // Verify transaction is soft deleted
        $this->expectException(\InvalidArgumentException::class);
        $this->service->getTransaction($transactionId);
    }

    /** @test */
    public function it_throws_when_deleting_nonexistent_transaction()
    {
        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction not found');
        
        $this->service->deleteTransaction(99999);
    }

    /** @test */
    public function it_throws_when_deleting_old_transaction()
    {
        // Arrange
        $oldDate = date('Y-m-d', strtotime('-31 days')); // More than 30 days ago
        $transactionId = $this->seedCashTransaction([
            'type' => 'RECEIPT',
            'amount' => 100000,
            'category' => 'sales',
            'transaction_date' => $oldDate
        ]);

        // Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Cannot delete transactions older than 30 days/');
        
        $this->service->deleteTransaction($transactionId);
    }

    // Helper methods for MySQL
    private function seedBranch(array $data = []): int
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

    private function seedUser(array $data = []): int
    {
        $payload = array_merge([
            'username' => 'user' . random_int(1000, 9999),
            'email' => 'user' . random_int(1000, 9999) . '@test.com',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('users')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedOrder(array $data): int
    {
        $payload = array_merge([
            'code' => 'HD' . random_int(1000, 9999),
            'total' => 0,
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('orders')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedPurchaseOrder(array $data): int
    {
        $payload = array_merge([
            'code' => 'PO' . random_int(1000, 9999),
            'total' => 0,
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('purchase_orders')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function seedCashTransaction(array $data): int
    {
        $branchId = $data['branch_id'] ?? $this->seedBranch();
        $userId = $data['created_by'] ?? $this->seedUser();
        
        $payload = array_merge([
            'type' => 'RECEIPT',
            'amount' => 100000,
            'category' => 'sales',
            'description' => 'Test transaction',
            'branch_id' => $branchId,
            'created_by' => $userId,
            'transaction_date' => date('Y-m-d'),
            'reference_type' => 'manual',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('cash_transactions')->insert($payload);
        return (int) $this->db->insertID();
    }
}