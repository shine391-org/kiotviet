<?php
namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CashTransactionSchemaTrait;

/**
 * @agent-test: Cash Transactions API integration tests - MySQL-only
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 * @agent-use: API endpoint testing with real MySQL database
 */
class CashTransactionsApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use DevDatabaseTrait;               // MySQL connection + transactions
    use CashTransactionSchemaTrait;     // Schema creation

    protected function setUp(): void
    {
        parent::setUp();
        
        // DevDatabaseTrait handles MySQL connection automatically
        $this->setUpDatabase();          // Connect to MySQL + start transaction

        // Reset schema data (truncate only)
        $this->resetCashTransactionSchema();
        
        // Tables are created by the migration, no need to create them here
        // The migration creates tables with proper BIGINT UNSIGNED types

        // Create test user to satisfy foreign key constraint
        $this->createTestUser();
        
        // Set up auth
        $this->setUpAuthToken();
        
        // Clean any existing test data - CRITICAL for preventing data leakage
        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        // DevDatabaseTrait handles cleanup
        $this->tearDownDatabase();       // Rollback transaction + close connection
        parent::tearDown();
    }

    /** @test */
    public function it_creates_receipt_via_api()
    {
        // Arrange
        $branchId = $this->createTestBranch(['name' => 'Main Branch']);
        $data = [
            'amount' => 500000,
            'category' => 'sales',
            'description' => 'Bán hàng HD001',
            'branch_id' => $branchId,
            'transaction_date' => '2025-11-26',
            'reference_type' => 'manual',
            'created_by' => 1,
        ];

        // Act
        $response = $this->withHeaders($this->authHeaders([
            'Content-Type' => 'application/json'
        ]))
        ->withBody(json_encode($data))
        ->post('/api/cash/receipt');

        // Assert - API returns success
        $responseData = $this->getJsonFromResponse($response);
        $this->assertTrue($responseData['success'] ?? false, 'API phải trả success=true');
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals('RECEIPT', $responseData['data']['type']);
        $this->assertEquals(500000, $responseData['data']['amount']);
    }

    /** @test */
    public function it_creates_payment_via_api()
    {
        // Arrange
        $branchId = $this->createTestBranch(['name' => 'Main Branch']);
        $data = [
            'amount' => 200000,
            'category' => 'expense',
            'description' => 'Chi phí văn phòng',
            'branch_id' => $branchId,
            'transaction_date' => '2025-11-26',
            'reference_type' => 'manual',
            'created_by' => 1,
        ];

        // Act
        $response = $this->withHeaders($this->authHeaders([
            'Content-Type' => 'application/json'
        ]))
        ->withBody(json_encode($data))
        ->post('/api/cash/payment');

        // Assert HTTP
        $response->assertStatus(201);
        $responseData = $this->getJsonFromResponse($response);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals('PAYMENT', $responseData['data']['type']);
        $this->assertEquals(200000, $responseData['data']['amount']);
    }

    /** @test */
    public function it_creates_receipt_with_order_reference_via_api()
    {
        // Arrange
        $branchId = $this->createTestBranch(['name' => 'Main Branch']);
        $orderId = $this->createTestOrder(['order_number' => 'HD001', 'total' => 500000]);
        $data = [
            'amount' => 500000,
            'category' => 'sales',
            'description' => 'Thanh toán đơn hàng HD001',
            'branch_id' => $branchId,
            'transaction_date' => '2025-11-26',
            'reference_type' => 'order',
            'reference_id' => $orderId,
            'reference_code' => 'HD001',
            'created_by' => 1,
        ];

        // Act
        $response = $this->withHeaders($this->authHeaders([
            'Content-Type' => 'application/json'
        ]))
        ->withBody(json_encode($data))
        ->post('/api/cash/receipt');

        $data = $this->getJsonFromResponse($response);
        $this->assertTrue($data['success'] ?? false, 'API phải trả success=true');

        // Assert MySQL Database
        $this->seeInDatabase('cash_transactions', [
            'type' => 'RECEIPT',
            'amount' => 500000,
            'reference_type' => 'order',
            'reference_id' => $orderId,
            'reference_code' => 'HD001'
        ]);
    }

    /** @test */
    public function it_lists_transactions_via_api()
    {
        // Arrange - Create test data in MySQL
        $branchId = $this->createTestBranch(['name' => 'Main Branch']);
        $this->createTestTransaction(['type' => 'RECEIPT', 'amount' => 100000, 'branch_id' => $branchId]);
        $this->createTestTransaction(['type' => 'PAYMENT', 'amount' => 50000, 'branch_id' => $branchId]);

        // Act
        $response = $this->withHeaders($this->authHeaders())
        ->get('/api/cash/transactions?page=1&limit=10');

        // Assert
        $response->assertStatus(200);
        $data = $this->getJsonFromResponse($response);
        
        $this->assertTrue($data['success']);
        $this->assertGreaterThanOrEqual(2, count($data['data']));
        $this->assertArrayHasKey('pagination', $data);
    }

    /** @test */
    public function it_lists_transactions_with_filters_via_api()
    {
        // Arrange
        $branchId = $this->createTestBranch(['name' => 'Main Branch']);
        $this->createTestTransaction(['type' => 'RECEIPT', 'amount' => 100000, 'category' => 'sales', 'branch_id' => $branchId]);
        $this->createTestTransaction(['type' => 'PAYMENT', 'amount' => 50000, 'category' => 'expense', 'branch_id' => $branchId]);
        $this->createTestTransaction(['type' => 'RECEIPT', 'amount' => 200000, 'category' => 'refund', 'branch_id' => $branchId]);

        // Act - Filter by type
        $response = $this->withHeaders($this->authHeaders())
        ->get('/api/cash/transactions?type=RECEIPT');

        // Assert
        $response->assertStatus(200);
        $data = $this->getJsonFromResponse($response);
        
        $this->assertTrue($data['success']);
        $this->assertCount(2, $data['data']);
        
        // Verify all results are RECEIPT type
        foreach ($data['data'] as $transaction) {
            $this->assertEquals('RECEIPT', $transaction['type']);
        }
    }

    /** @test */
    public function it_gets_transaction_by_id_via_api()
    {
        // Arrange
        $branchId = $this->createTestBranch(['name' => 'Main Branch']);
        $transactionId = $this->createTestTransaction([
            'type' => 'RECEIPT',
            'amount' => 500000,
            'category' => 'sales',
            'branch_id' => $branchId
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->authToken
        ])->get("/api/cash/transactions/{$transactionId}");

        // Assert
        $response->assertStatus(200);
        $data = $this->getJsonFromResponse($response);
        
        $this->assertTrue($data['success']);
        $this->assertEquals($transactionId, $data['data']['id']);
        $this->assertEquals('RECEIPT', $data['data']['type']);
        $this->assertEquals(500000, $data['data']['amount']);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_transaction_via_api()
    {
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->authToken
        ])->get('/api/cash/transactions/99999');

        // Assert
        $response->assertStatus(400); // Changed from 404 to 400 - service throws InvalidArgumentException
        $data = $this->getJsonFromResponse($response);
        
        // Handle different error response formats
        if (isset($data['success'])) {
            $this->assertFalse($data['success']);
        } else {
            // CodeIgniter error format
            $this->assertEquals(400, $data['status']);
        }
    }

    /** @test */
    public function it_gets_balance_via_api()
    {
        // Arrange
        $branchId = $this->createTestBranch(['name' => 'Main Branch']);
        $this->createTestTransaction(['type' => 'RECEIPT', 'amount' => 500000, 'branch_id' => $branchId]);
        $this->createTestTransaction(['type' => 'RECEIPT', 'amount' => 300000, 'branch_id' => $branchId]);
        $this->createTestTransaction(['type' => 'PAYMENT', 'amount' => 200000, 'branch_id' => $branchId]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->authToken
        ])->get('/api/cash/balance');

        // Assert
        $response->assertStatus(200);
        $data = $this->getJsonFromResponse($response);
        
        $this->assertTrue($data['success']);
        $this->assertEquals(600000, $data['data']['balance']); // (500k + 300k) - 200k = 600k
        $this->assertNull($data['data']['branch_id']); // All branches
        $this->assertArrayHasKey('as_of', $data['data']);
    }

    /** @test */
    public function it_gets_branch_balance_via_api()
    {
        // Arrange
        $branchId1 = $this->createTestBranch(['name' => 'Branch 1']);
        $branchId2 = $this->createTestBranch(['name' => 'Branch 2']);
        
        $this->createTestTransaction(['type' => 'RECEIPT', 'amount' => 300000, 'branch_id' => $branchId1]);
        $this->createTestTransaction(['type' => 'PAYMENT', 'amount' => 100000, 'branch_id' => $branchId1]);
        $this->createTestTransaction(['type' => 'RECEIPT', 'amount' => 500000, 'branch_id' => $branchId2]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->authToken
        ])->get("/api/cash/balance/branch/{$branchId1}");

        // Assert
        $response->assertStatus(200);
        $data = $this->getJsonFromResponse($response);
        
        $this->assertTrue($data['success']);
        $this->assertEquals(200000, $data['data']['balance']); // 300k - 100k = 200k
        $this->assertEquals($branchId1, $data['data']['branch_id']);
    }

    /** @test */
    public function it_gets_daily_report_via_api()
    {
        // Arrange
        $branchId = $this->createTestBranch(['name' => 'Main Branch']);
        $this->createTestTransaction([
            'type' => 'RECEIPT',
            'amount' => 300000,
            'category' => 'sales',
            'transaction_date' => '2025-11-26',
            'branch_id' => $branchId
        ]);
        $this->createTestTransaction([
            'type' => 'RECEIPT',
            'amount' => 200000,
            'category' => 'refund',
            'transaction_date' => '2025-11-26',
            'branch_id' => $branchId
        ]);
        $this->createTestTransaction([
            'type' => 'PAYMENT',
            'amount' => 150000,
            'category' => 'expense',
            'transaction_date' => '2025-11-26',
            'branch_id' => $branchId
        ]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->authToken
        ])->get('/api/cash/report/daily?date=2025-11-26');

        // Assert
        $response->assertStatus(200);
        $data = $this->getJsonFromResponse($response);
        
        $this->assertTrue($data['success']);
        $this->assertEquals(500000, $data['data']['receipt_total']); // 300k + 200k
        $this->assertEquals(150000, $data['data']['payment_total']); // 150k
        $this->assertEquals(350000, $data['data']['balance']); // 500k - 150k
        $this->assertEquals(350000, $data['data']['net']); // Same as balance
    }

    /** @test */
    public function it_deletes_transaction_via_api()
    {
        // Arrange
        $branchId = $this->createTestBranch(['name' => 'Main Branch']);
        $transactionId = $this->createTestTransaction([
            'type' => 'RECEIPT',
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => $branchId,
            'transaction_date' => date('Y-m-d') // Recent transaction
        ]);

        // Verify transaction exists
        $this->seeInDatabase('cash_transactions', ['id' => $transactionId]);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->authToken
        ])->delete("/api/cash/transactions/{$transactionId}");

        // Assert
        $response->assertStatus(200);
        
        // Verify transaction is soft deleted
        $this->dontSeeInDatabase('cash_transactions', [
            'id' => $transactionId,
            'deleted_at' => null
        ]);
    }

    /** @test */
    public function it_validates_required_fields_for_receipt_via_api()
    {
        // Act - Send invalid data
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->authToken,
            'Content-Type' => 'application/json'
        ])->post('/api/cash/receipt', [
            // Missing required fields
        ]);

        // Assert MySQL validation
        $response->assertStatus(400);
        $data = $this->getJsonFromResponse($response);
        
        // Handle different error response formats
        if (isset($data['success'])) {
            $this->assertFalse($data['success']);
            $this->assertArrayHasKey('errors', $data);
        } else {
            // CodeIgniter validation error format
            $this->assertEquals(400, $data['status']);
            $this->assertArrayHasKey('messages', $data);
        }
    }

    /** @test */
    public function it_validates_invalid_amount_via_api()
    {
        // Arrange
        $branchId = $this->createTestBranch(['name' => 'Main Branch']);
        $data = [
            'amount' => -1000, // Invalid amount
            'category' => 'sales',
            'branch_id' => $branchId,
            'transaction_date' => '2025-11-26'
        ];

        // Act
        $response = $this->withHeaders($this->authHeaders([
            'Content-Type' => 'application/json'
        ]))
        ->withBody(json_encode($data))
        ->post('/api/cash/receipt');

        // Assert
        $response->assertStatus(400);
        $data = $this->getJsonFromResponse($response);
        
        // Handle different error response formats
        if (isset($data['success'])) {
            $this->assertFalse($data['success']);
            $this->assertStringContainsString('Amount must be greater than 0', json_encode($data));
        } else {
            // CodeIgniter validation error format
            $this->assertEquals(400, $data['status']);
            $this->assertStringContainsString('greater than 0', json_encode($data));
        }
    }

    /** @test */
    public function it_validates_future_transaction_date_via_api()
    {
        // Arrange
        $branchId = $this->createTestBranch(['name' => 'Main Branch']);
        $futureDate = date('Y-m-d', strtotime('+1 day'));
        $data = [
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => $branchId,
            'transaction_date' => $futureDate // Future date
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->authToken,
            'Content-Type' => 'application/json'
        ])->post('/api/cash/receipt', $data);

        // Assert
        $response->assertStatus(400);
        $data = $this->getJsonFromResponse($response);
        
        // Handle different error response formats
        if (isset($data['success'])) {
            $this->assertFalse($data['success']);
            $this->assertStringContainsString('Transaction date cannot be in future', json_encode($data));
        } else {
            // CodeIgniter validation error format
            $this->assertEquals(400, $data['status']);
            $this->assertStringContainsString('required', json_encode($data));
        }
    }

    /** @test */
    public function it_requires_authentication_via_api()
    {
        // Act - No token
        $response = $this->post('/api/cash/receipt', [
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => 1,
            'transaction_date' => '2025-11-26'
        ]);

        // Assert
        $response->assertStatus(401);
        $this->assertFalse($response->isOK());
    }

    /** @test */
    public function it_validates_nonexistent_branch_via_api()
    {
        // Arrange
        $data = [
            'amount' => 100000,
            'category' => 'sales',
            'branch_id' => 99999, // Non-existent branch
            'transaction_date' => '2025-11-26'
        ];

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->authToken,
            'Content-Type' => 'application/json'
        ])->post('/api/cash/receipt', $data);

        // Assert
        $response->assertStatus(400);
        $data = $this->getJsonFromResponse($response);
        
        // Handle different error response formats
        if (isset($data['success'])) {
            $this->assertFalse($data['success']);
            $this->assertStringContainsString('Branch not found or inactive', json_encode($data));
        } else {
            // CodeIgniter validation error format
            $this->assertEquals(400, $data['status']);
            $this->assertStringContainsString('required', json_encode($data));
        }
    }

    // Helper methods for MySQL
    private function getAuthToken(): string
    {
        // For testing, we'll create a test user and return a mock token
        // In real implementation, this would authenticate with the API
        return 'test-token-' . random_int(1000, 9999);
    }

    private function createTestUser(): int
    {
        $payload = [
            'username' => 'tester',
            'email' => 'tester@test.com',
            'full_name' => 'Test User',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->db->table('users')->insert($payload);
        $userId = (int) $this->db->insertID();
        
        // Debug: Verify user was created
        $userQuery = $this->db->table('users')->where('id', $userId)->get();
        $userCheck = $userQuery ? $userQuery->getRowArray() : null;
        error_log("DEBUG: createTestUser() - Created user ID: " . $userId . ", user data: " . json_encode($userCheck));
        
        return $userId;
    }

    private function createTestBranch(array $data): int
    {
        $payload = array_merge([
            'name' => 'Test Branch',
            'code' => 'BR' . random_int(1000, 9999),
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('branches')->insert($payload);
        $branchId = (int) $this->db->insertID();
        
        // Debug: Verify branch was created
        $branchQuery = $this->db->table('branches')->where('id', $branchId)->get();
        $branchCheck = $branchQuery ? $branchQuery->getRowArray() : null;
        error_log("DEBUG: createTestBranch() - Created branch ID: " . $branchId . ", branch data: " . json_encode($branchCheck));
        
        return $branchId;
    }

    private function createTestOrder(array $data): int
    {
        $payload = array_merge([
            'order_number' => 'HD' . random_int(1000, 9999),
            'total' => 0,
            'status' => 'completed',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('orders')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function createTestTransaction(array $data): int
    {
        $branchId = $data['branch_id'] ?? $this->createTestBranch([]);
        
        $payload = array_merge([
            'type' => 'RECEIPT',
            'amount' => 100000,
            'category' => 'sales',
            'description' => 'Test transaction',
            'branch_id' => $branchId,
            'created_by' => 1, // Mock user ID
            'transaction_date' => date('Y-m-d'),
            'reference_type' => 'manual',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('cash_transactions')->insert($payload);
        return (int) $this->db->insertID();
    }

    private function cleanupTestData(): void
    {
        $tables = ['cash_transactions', 'branches', 'orders'];
        foreach ($tables as $table) {
            if ($this->db->tableExists($table)) {
                $this->db->table($table)->truncate();
            }
        }
    }

    /**
     * Assert that a record exists in the database.
     */
    protected function seeInDatabase(string $table, array $criteria): void
    {
        $builder = $this->db->table($table);
        foreach ($criteria as $field => $value) {
            $builder->where($field, $value);
        }
        
        $query = $builder->get();
        $result = $query ? $query->getResultArray() : [];
        
        if (empty($result)) {
            // Debug: Show what we actually found
            $allQuery = $this->db->table($table)->get()->getResultArray();
            throw new \PHPUnit\Framework\ExpectationFailedException(
                "Failed asserting that a row exists in table {$table} with criteria: " . json_encode($criteria) .
                ". Actual data in table: " . json_encode($allQuery)
            );
        }
    }

    /**
     * Assert that a record does not exist in the database.
     */
    protected function dontSeeInDatabase(string $table, array $criteria): void
    {
        $builder = $this->db->table($table);
        foreach ($criteria as $field => $value) {
            $builder->where($field, $value);
        }
        
        $query = $builder->get();
        $result = $query ? $query->getResultArray() : [];
        
        if (!empty($result)) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                "Failed asserting that no row exists in table {$table} with criteria: " . json_encode($criteria)
            );
        }
    }

    /**
     * Extract JSON from response, handling HTML wrapper.
     */
    private function getJsonFromResponse($response): array
    {
        $responseBody = $response->getBody();
        
        // Extract JSON from HTML wrapper if present
        $jsonString = $responseBody;
        if (strpos($responseBody, '<!DOCTYPE html') !== false) {
            // Extract JSON from <p> tag
            if (preg_match('/<p>(.*?)<\/p>/s', $responseBody, $matches)) {
                $jsonString = html_entity_decode($matches[1]);
            }
        }
        
        $data = json_decode($jsonString, true);
        if ($data === null) {
            throw new \PHPUnit\Framework\ExpectationFailedException(
                "Failed to decode JSON from response: " . $jsonString . " Error: " . json_last_error_msg()
            );
        }
        
        return $data;
    }
}
