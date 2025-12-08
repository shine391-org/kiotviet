<?php

namespace Tests\Services;

use App\Services\CashTransactions\CashTransactionService;
use App\Repositories\CashTransactions\CashTransactionRepository;
use App\Validators\CashTransactionValidator;
use App\Validators\CashTransactionReferenceValidator;
use App\Models\CashTransactionModel;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: CashTransactionService (stubbed)
 * @agent-pattern: Service orchestration without DB
 */
class CashTransactionServiceTest extends CIUnitTestCase
{
    private CashTransactionService $service;
    private CashTransactionServiceFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new CashTransactionServiceFakeRepo();
        $validator = new CashTransactionValidator();
        $refValidator = new class extends CashTransactionReferenceValidator {
            public function __construct() {}
            public function validateReference(string $type, int $id, float $amount): array { return []; }
        };
        $this->service = new CashTransactionService($this->repo, $validator, $refValidator);
    }

    public function testCreateReceiptReturnsTransaction(): void
    {
        $data = [
            'type' => 'RECEIPT',
            'category' => 'sales',
            'amount' => 500000,
            'transaction_date' => date('Y-m-d'),
            'description' => 'Test receipt',
            'created_by' => 1,
            'branch_id' => 1,
        ];

        $result = $this->service->createReceipt($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('RECEIPT', $result['data']['type']);
        $this->assertSame(500000, (int) $result['data']['amount']);
    }

    public function testCreateReceiptValidatesAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->createReceipt([
            'type' => 'RECEIPT',
            'category' => 'sales',
            'amount' => -100,
            'transaction_date' => date('Y-m-d'),
            'created_by' => 1,
            'branch_id' => 1,
        ]);
    }

    public function testCreateReceiptRequiresCreatedBy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('created_by field is required');

        $this->service->createReceipt([
            'type' => 'RECEIPT',
            'category' => 'sales',
            'amount' => 100000,
            'transaction_date' => date('Y-m-d'),
            'branch_id' => 1,
        ]);
    }

    public function testCreatePaymentReturnsTransaction(): void
    {
        $data = [
            'type' => 'PAYMENT',
            'category' => 'expense',
            'amount' => 200000,
            'transaction_date' => date('Y-m-d'),
            'description' => 'Test payment',
            'created_by' => 1,
            'branch_id' => 1,
        ];

        $result = $this->service->createPayment($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('PAYMENT', $result['data']['type']);
    }

    public function testCreatePaymentRequiresCreatedBy(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('created_by field is required');

        $this->service->createPayment([
            'type' => 'PAYMENT',
            'category' => 'expense',
            'amount' => 100000,
            'transaction_date' => date('Y-m-d'),
            'branch_id' => 1,
        ]);
    }

    public function testGetTransactionReturnsData(): void
    {
        $result = $this->service->getTransaction(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['data']['id']);
    }

    public function testGetTransactionThrowsWhenNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction not found');
        $this->service->getTransaction(999);
    }

    public function testListTransactionsReturnsPaginated(): void
    {
        $result = $this->service->listTransactions([], 1, 10);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertCount(2, $result['data']);
    }

    public function testListTransactionsWithTypeFilter(): void
    {
        $result = $this->service->listTransactions(['type' => 'RECEIPT'], 1, 10);

        $this->assertTrue($result['success']);
    }

    public function testListTransactionsWithDateFilter(): void
    {
        $result = $this->service->listTransactions([
            'from_date' => '2024-01-01',
            'to_date' => '2024-12-31',
        ], 1, 10);

        $this->assertTrue($result['success']);
    }

    public function testGetBalanceReturnsAmount(): void
    {
        $result = $this->service->getBalance(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('balance', $result['data']);
        $this->assertSame(1, $result['data']['branch_id']);
        $this->assertSame('VND', $result['data']['currency']);
    }

    public function testGetBalanceWithFilters(): void
    {
        $result = $this->service->getBalance(1, [
            'from_date' => '2024-01-01',
            'to_date' => '2024-06-30',
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('balance', $result['data']);
    }

    public function testGetDailyReportReturnsSummary(): void
    {
        $result = $this->service->getDailyReport('2024-06-15', 1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total_receipts', $result['data']);
        $this->assertArrayHasKey('total_payments', $result['data']);
        $this->assertArrayHasKey('balance', $result['data']);
        $this->assertArrayHasKey('net', $result['data']);
    }

    public function testGetDailyReportRequiresDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Date parameter is required');
        $this->service->getDailyReport('', 1);
    }

    public function testGetDailyReportValidatesDateFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date format');
        $this->service->getDailyReport('invalid-date', 1);
    }

    public function testDeleteTransactionReturnsSuccess(): void
    {
        $result = $this->service->deleteTransaction(1);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('deleted', $result['message']);
    }

    public function testDeleteTransactionThrowsWhenNotFound(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaction not found');
        $this->service->deleteTransaction(999);
    }

    public function testDeleteTransactionThrowsWhenOlderThan30Days(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot delete transactions older than 30 days');
        $this->service->deleteTransaction(2); // Transaction 2 is old
    }

    public function testGetTypeDisplayName(): void
    {
        $this->assertSame('Thu', CashTransactionService::getTypeDisplayName(CashTransactionModel::TYPE_RECEIPT));
        $this->assertSame('Chi', CashTransactionService::getTypeDisplayName(CashTransactionModel::TYPE_PAYMENT));
        $this->assertSame('other', CashTransactionService::getTypeDisplayName('other'));
    }

    public function testGetCategoryDisplayName(): void
    {
        $this->assertSame('Bán hàng', CashTransactionService::getCategoryDisplayName(CashTransactionModel::CATEGORY_SALES));
        $this->assertSame('Lương', CashTransactionService::getCategoryDisplayName(CashTransactionModel::CATEGORY_SALARY));
        $this->assertSame('custom', CashTransactionService::getCategoryDisplayName('custom'));
    }
}

class CashTransactionServiceFakeRepo extends CashTransactionRepository
{
    private array $transactions = [];

    public function __construct()
    {
        $this->transactions = [
            1 => [
                'id' => 1,
                'type' => 'RECEIPT',
                'category' => 'sales',
                'amount' => 500000,
                'transaction_date' => date('Y-m-d'),
                'description' => 'Test receipt',
                'payment_method' => 'cash',
                'status' => 'approved',
                'branch_id' => 1,
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s'),
            ],
            2 => [
                'id' => 2,
                'type' => 'PAYMENT',
                'category' => 'expense',
                'amount' => 200000,
                'transaction_date' => date('Y-m-d', strtotime('-60 days')), // Old transaction
                'description' => 'Old payment',
                'payment_method' => 'cash',
                'status' => 'approved',
                'branch_id' => 1,
                'created_by' => 1,
                'created_at' => date('Y-m-d H:i:s', strtotime('-60 days')),
            ],
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->transactions[$id] ?? null;
    }

    public function create(array $data): array
    {
        $id = count($this->transactions) > 0 ? max(array_keys($this->transactions)) + 1 : 1;
        $data['id'] = $id;
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->transactions[$id] = $data;
        return $data;
    }

    public function list(array $filters, int $page = 1, int $limit = 20): array
    {
        return [
            'data' => array_values($this->transactions),
            'total' => count($this->transactions),
        ];
    }

    public function calculateBalance(?int $branchId = null, ?array $filters = null): float
    {
        $balance = 0;
        foreach ($this->transactions as $t) {
            if ($branchId && ($t['branch_id'] ?? null) !== $branchId) {
                continue;
            }
            if ($t['type'] === 'RECEIPT') {
                $balance += $t['amount'];
            } else {
                $balance -= $t['amount'];
            }
        }
        return $balance;
    }

    public function getDailySummary(string $date, ?int $branchId = null): array
    {
        return [
            'date' => $date,
            'branch_id' => $branchId,
            'total_receipts' => 500000,
            'total_payments' => 200000,
            'balance' => 300000,
        ];
    }

    public function softDelete(int $id): bool
    {
        if (! isset($this->transactions[$id])) {
            return false;
        }
        $this->transactions[$id]['deleted_at'] = date('Y-m-d H:i:s');
        return true;
    }
}
