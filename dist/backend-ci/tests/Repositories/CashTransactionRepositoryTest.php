<?php
namespace Tests\Repositories;

use App\Repositories\CashTransactions\CashTransactionRepository;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CashTransactionSchemaTrait;

/**
 * @agent-test: CashTransactionRepository unit testing
 * @agent-pattern: Repository test with DevDatabaseTrait + SchemaTrait (MySQL-only)
 */
class CashTransactionRepositoryTest extends CIUnitTestCase
{
    use DevDatabaseTrait;              // MySQL connection + transactions
    use CashTransactionSchemaTrait;    // Schema creation for cash transactions

    private CashTransactionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();          // Connect to MySQL + start transaction
        
        // Skip transaction for table creation - create directly in main database
        $this->db->transComplete(); // End transaction
        
        // Create cash transaction schema (now includes supporting tables)
        $this->resetCashTransactionSchema();     // Creates all tables in MySQL
        
        // Skip transaction for now - work directly with main database
        // $this->db->transStart();
        
        $this->repository = new CashTransactionRepository();
    }

    protected function tearDown(): void
    {
        // Clean up test data manually since we're not using transactions
        try {
            $this->db->query("DELETE FROM cash_transactions");
            $this->db->query("DELETE FROM purchase_orders");
            $this->db->query("DELETE FROM orders");
            $this->db->query("DELETE FROM users");
            $this->db->query("DELETE FROM branches");
        } catch (\Exception $e) {
            // Ignore cleanup errors
        }
        
        // $this->tearDownDatabase();      // Skip rollback since we're not using transactions
        parent::tearDown();
    }

    /** @test */
    public function it_creates_cash_transaction()
    {
        // Arrange
        $branchId = $this->seedBranch(['name' => 'Main Branch']);
        $userId = $this->seedUser(['username' => 'testuser']);
        $data = [
            'type' => 'RECEIPT',
            'amount' => 500000,
            'category' => 'sales',
            'description' => 'Bán hàng HD001',
            'branch_id' => $branchId,
            'created_by' => $userId,
            'transaction_date' => '2025-11-26',
            'reference_type' => 'manual'
        ];

        // Act
        $result = $this->repository->create($data);

        // Assert
        $this->assertIsArray($result);
        $this->assertNotEmpty($result['id']);
        $this->assertEquals('RECEIPT', $result['type']);
        $this->assertEquals(500000, $result['amount']);
        $this->assertEquals('sales', $result['category']);
        $this->assertEquals($branchId, $result['branch_id']);
        $this->assertEquals($userId, $result['created_by']);
        
        // Verify in MySQL database
        $row = $this->db->table('cash_transactions')
            ->where('id', $result['id'])
            ->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertEquals('RECEIPT', $row['type']);
    }

    /** @test */
    public function it_finds_transaction_by_id()
    {
        // Arrange
        $transactionId = $this->seedCashTransaction([
            'type' => 'PAYMENT',
            'amount' => 200000,
            'category' => 'expense'
        ]);

        // Act
        $result = $this->repository->findById($transactionId);

        // Assert
        $this->assertIsArray($result);
        $this->assertEquals($transactionId, $result['id']);
        $this->assertEquals('PAYMENT', $result['type']);
        $this->assertEquals(200000, $result['amount']);
        $this->assertEquals('expense', $result['category']);
    }

    /** @test */
    public function it_returns_null_for_nonexistent_transaction()
    {
        // Act
        $result = $this->repository->findById(99999);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_returns_null_for_soft_deleted_transaction()
    {
        // Arrange
        $transactionId = $this->seedCashTransaction([
            'type' => 'RECEIPT',
            'amount' => 100000,
            'category' => 'sales'
        ]);

        // Soft delete the transaction
        $this->db->table('cash_transactions')
            ->where('id', $transactionId)
            ->update(['deleted_at' => date('Y-m-d H:i:s')]);

        // Act
        $result = $this->repository->findById($transactionId);

        // Assert
        $this->assertNull($result);
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

        // Act - Filter by type
        $result = $this->repository->list(['type' => 'RECEIPT'], 1, 10);

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertCount(2, $result['data']);
        $this->assertEquals(2, $result['total']);
        
        // Verify all results are RECEIPT type
        foreach ($result['data'] as $transaction) {
            $this->assertEquals('RECEIPT', $transaction['type']);
        }
    }

    /** @test */
    public function it_lists_transactions_with_category_filter()
    {
        // Arrange
        $this->seedCashTransaction(['type' => 'RECEIPT', 'amount' => 100000, 'category' => 'sales']);
        $this->seedCashTransaction(['type' => 'RECEIPT', 'amount' => 50000, 'category' => 'refund']);
        $this->seedCashTransaction(['type' => 'RECEIPT', 'amount' => 75000, 'category' => 'sales']);

        // Act
        $result = $this->repository->list(['category' => 'sales'], 1, 10);

        // Assert
        $this->assertCount(2, $result['data']);
        $this->assertEquals(2, $result['total']);
        
        // Verify all results are sales category
        foreach ($result['data'] as $transaction) {
            $this->assertEquals('sales', $transaction['category']);
        }
    }

    /** @test */
    public function it_lists_transactions_with_date_range_filter()
    {
        // Arrange
        $this->seedCashTransaction([
            'type' => 'RECEIPT',
            'amount' => 100000,
            'transaction_date' => '2025-11-25'
        ]);
        $this->seedCashTransaction([
            'type' => 'RECEIPT',
            'amount' => 200000,
            'transaction_date' => '2025-11-26'
        ]);
        $this->seedCashTransaction([
            'type' => 'RECEIPT',
            'amount' => 300000,
            'transaction_date' => '2025-11-27'
        ]);

        // Act
        $result = $this->repository->list([
            'date_from' => '2025-11-25',
            'date_to' => '2025-11-26'
        ], 1, 10);

        // Assert
        $this->assertCount(2, $result['data']);
        $this->assertEquals(2, $result['total']);
    }

    /** @test */
    public function it_lists_transactions_with_pagination()
    {
        // Arrange
        for ($i = 1; $i <= 5; $i++) {
            $this->seedCashTransaction([
                'type' => 'RECEIPT',
                'amount' => 100000 * $i,
                'category' => 'sales'
            ]);
        }

        // Act - Page 1 with limit 2
        $result = $this->repository->list([], 1, 2);

        // Assert
        $this->assertCount(2, $result['data']);
        $this->assertEquals(5, $result['total']);

        // Act - Page 2 with limit 2
        $result2 = $this->repository->list([], 2, 2);

        // Assert
        $this->assertCount(2, $result2['data']);
        $this->assertEquals(5, $result2['total']);
    }

    /** @test */
    public function it_calculates_balance_for_all_branches()
    {
        // Arrange
        $this->seedCashTransaction(['type' => 'RECEIPT', 'amount' => 500000, 'category' => 'sales']);
        $this->seedCashTransaction(['type' => 'RECEIPT', 'amount' => 300000, 'category' => 'refund']);
        $this->seedCashTransaction(['type' => 'PAYMENT', 'amount' => 200000, 'category' => 'expense']);
        $this->seedCashTransaction(['type' => 'PAYMENT', 'amount' => 100000, 'category' => 'purchase']);

        // Act
        $balance = $this->repository->calculateBalance();

        // Assert
        $this->assertEquals(500000, $balance); // (500k + 300k) - (200k + 100k) = 500k
    }

    /** @test */
    public function it_calculates_balance_for_specific_branch()
    {
        // Arrange
        $branchId1 = $this->seedBranch(['name' => 'Branch 1']);
        $branchId2 = $this->seedBranch(['name' => 'Branch 2']);
        
        $this->seedCashTransaction(['type' => 'RECEIPT', 'amount' => 300000, 'branch_id' => $branchId1]);
        $this->seedCashTransaction(['type' => 'PAYMENT', 'amount' => 100000, 'branch_id' => $branchId1]);
        $this->seedCashTransaction(['type' => 'RECEIPT', 'amount' => 500000, 'branch_id' => $branchId2]);

        // Act
        $balance1 = $this->repository->calculateBalance($branchId1);
        $balance2 = $this->repository->calculateBalance($branchId2);

        // Assert
        $this->assertEquals(200000, $balance1); // 300k - 100k = 200k
        $this->assertEquals(500000, $balance2); // 500k - 0 = 500k
    }

    /** @test */
    public function it_gets_daily_summary()
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
        $summary = $this->repository->getDailySummary('2025-11-26');

        // Assert
        $this->assertIsArray($summary);
        $this->assertEquals(500000, $summary['receipt_total']); // 300k + 200k
        $this->assertEquals(200000, $summary['payment_total']); // 150k + 50k
        $this->assertEquals(300000, $summary['balance']); // 500k - 200k
    }

    /** @test */
    public function it_gets_daily_summary_for_specific_branch()
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
        $summary = $this->repository->getDailySummary('2025-11-26', $branchId1);

        // Assert
        $this->assertEquals(300000, $summary['receipt_total']);
        $this->assertEquals(100000, $summary['payment_total']);
        $this->assertEquals(200000, $summary['balance']);
    }

    /** @test */
    public function it_soft_deletes_transaction()
    {
        // Arrange
        $transactionId = $this->seedCashTransaction([
            'type' => 'RECEIPT',
            'amount' => 100000,
            'category' => 'sales'
        ]);

        // Verify transaction exists
        $before = $this->repository->findById($transactionId);
        $this->assertNotNull($before);

        // Act
        $result = $this->repository->softDelete($transactionId);

        // Assert
        $this->assertTrue($result);
        
        // Verify transaction is soft deleted
        $after = $this->repository->findById($transactionId);
        $this->assertNull($after);
        
        // Verify soft delete timestamp in database
        $row = $this->db->table('cash_transactions')
            ->where('id', $transactionId)
            ->get()->getRowArray();
        $this->assertNotNull($row['deleted_at']);
    }

    /** @test */
    public function it_returns_false_when_soft_deleting_nonexistent_transaction()
    {
        // Act
        $result = $this->repository->softDelete(99999);

        // Assert
        $this->assertFalse($result);
    }

    /** @test */
    public function it_excludes_soft_deleted_transactions_from_balance_calculation()
    {
        // Arrange
        $this->seedCashTransaction(['type' => 'RECEIPT', 'amount' => 500000, 'category' => 'sales']);
        $transactionId = $this->seedCashTransaction(['type' => 'PAYMENT', 'amount' => 200000, 'category' => 'expense']);

        // Verify balance before soft delete
        $balanceBefore = $this->repository->calculateBalance();
        $this->assertEquals(300000, $balanceBefore); // 500k - 200k = 300k

        // Act - Soft delete the payment
        $this->repository->softDelete($transactionId);

        // Assert - Balance should change
        $balanceAfter = $this->repository->calculateBalance();
        $this->assertEquals(500000, $balanceAfter); // 500k - 0 = 500k
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

        // Use raw SQL like validator test
        $sql = "INSERT INTO branches (name, code, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $payload['name'],
            $payload['code'],
            $payload['status'],
            $payload['created_at'],
            $payload['updated_at']
        ]);
        $insertId = $this->db->insertID();
        
        if ($insertId === 0) {
            throw new \Exception('Branch insert failed: ' . json_encode($payload));
        }
        
        return (int) $insertId;
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

        // Use raw SQL like validator test
        $sql = "INSERT INTO users (username, email, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $payload['username'],
            $payload['email'],
            $payload['status'],
            $payload['created_at'],
            $payload['updated_at']
        ]);
        $insertId = $this->db->insertID();
        
        if ($insertId === 0) {
            throw new \Exception('User insert failed: ' . json_encode($payload));
        }
        
        return (int) $insertId;
    }

    private function seedCashTransaction(array $data = []): int
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

        // Use raw SQL for consistency
        $sql = "INSERT INTO cash_transactions (type, amount, category, description, branch_id, created_by, transaction_date, reference_type, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->query($sql, [
            $payload['type'],
            $payload['amount'],
            $payload['category'],
            $payload['description'],
            $payload['branch_id'],
            $payload['created_by'],
            $payload['transaction_date'],
            $payload['reference_type'],
            $payload['created_at'],
            $payload['updated_at']
        ]);
        $insertId = $this->db->insertID();
        
        if ($insertId === 0) {
            throw new \Exception('Cash transaction insert failed: ' . json_encode($payload));
        }
        
        return (int) $insertId;
    }
}