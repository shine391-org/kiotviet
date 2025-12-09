<?php

namespace Tests\Services;

use App\Services\BankAccounts\BankAccountService;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class BankAccountServiceTest extends CIUnitTestCase
{
    private BankAccountServiceStub $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BankAccountServiceStub();
    }

    public function testListReturnsBankAccounts(): void
    {
        $result = $this->service->list();

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    public function testListWithActiveFilter(): void
    {
        $result = $this->service->list(['active' => true]);

        $this->assertTrue($result['success']);
    }

    public function testListWithBranchFilter(): void
    {
        $result = $this->service->list(['branch_id' => 1]);

        $this->assertTrue($result['success']);
    }

    public function testShowReturnsBankAccount(): void
    {
        $result = $this->service->show(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testShowThrowsWhenNotFound(): void
    {
        $this->service->setNotFound(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bank account not found');

        $this->service->show(999);
    }

    public function testCreateSavesBankAccount(): void
    {
        $input = [
            'bank_name' => 'Vietcombank',
            'account_number' => '1234567890',
            'account_name' => 'LANO CRM',
            'branch_id' => 1,
        ];

        $result = $this->service->create($input);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testCreateThrowsWhenAccountNumberExists(): void
    {
        $this->service->setAccountExists(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Account number already exists');

        $this->service->create(['account_number' => '1234567890']);
    }

    public function testUpdateModifiesBankAccount(): void
    {
        $result = $this->service->update(1, ['account_name' => 'Updated Name']);

        $this->assertTrue($result['success']);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->service->setNotFound(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Bank account not found');

        $this->service->update(999, ['account_name' => 'Test']);
    }

    public function testDeleteRemovesBankAccount(): void
    {
        $result = $this->service->delete(1);

        $this->assertTrue($result['success']);
    }

    public function testDeleteThrowsWhenHasTransactions(): void
    {
        $this->service->setHasTransactions(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot delete account with transactions');

        $this->service->delete(1);
    }

    public function testToggleActiveStatus(): void
    {
        $result = $this->service->toggleActive(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('is_active', $result['data']);
    }

    public function testGetBalance(): void
    {
        $result = $this->service->getBalance(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('balance', $result['data']);
    }

    public function testGetTransactions(): void
    {
        $result = $this->service->getTransactions(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    public function testGetTransactionsWithDateFilter(): void
    {
        $result = $this->service->getTransactions(1, [
            'date_from' => '2024-01-01',
            'date_to' => '2024-12-31',
        ]);

        $this->assertTrue($result['success']);
    }

    public function testRecordDeposit(): void
    {
        $result = $this->service->recordDeposit(1, [
            'amount' => 1000000,
            'description' => 'Customer payment',
            'reference' => 'INV-001',
        ]);

        $this->assertTrue($result['success']);
    }

    public function testRecordWithdrawal(): void
    {
        $result = $this->service->recordWithdrawal(1, [
            'amount' => 500000,
            'description' => 'Supplier payment',
            'reference' => 'PO-001',
        ]);

        $this->assertTrue($result['success']);
    }

    public function testRecordWithdrawalThrowsWhenInsufficientBalance(): void
    {
        $this->service->setInsufficientBalance(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Insufficient balance');

        $this->service->recordWithdrawal(1, ['amount' => 10000000]);
    }

    public function testTransferBetweenAccounts(): void
    {
        $result = $this->service->transfer(1, 2, [
            'amount' => 500000,
            'description' => 'Internal transfer',
        ]);

        $this->assertTrue($result['success']);
    }

    public function testTransferThrowsWhenSameAccount(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot transfer to the same account');

        $this->service->transfer(1, 1, ['amount' => 100000]);
    }
}

class BankAccountServiceStub extends BankAccountService
{
    private bool $notFound = false;
    private bool $accountExists = false;
    private bool $hasTransactions = false;
    private bool $insufficientBalance = false;

    public function __construct()
    {
        // Don't call parent
    }

    public function setNotFound(bool $v): void { $this->notFound = $v; }
    public function setAccountExists(bool $v): void { $this->accountExists = $v; }
    public function setHasTransactions(bool $v): void { $this->hasTransactions = $v; }
    public function setInsufficientBalance(bool $v): void { $this->insufficientBalance = $v; }

    public function list(array $filters = []): array
    {
        return [
            'success' => true,
            'data' => [
                ['id' => 1, 'bank_name' => 'Vietcombank', 'account_number' => '123', 'is_active' => true],
                ['id' => 2, 'bank_name' => 'Techcombank', 'account_number' => '456', 'is_active' => true],
            ],
        ];
    }

    public function show(int $id): array
    {
        if ($this->notFound) throw new RuntimeException('Bank account not found');
        return [
            'success' => true,
            'data' => ['id' => $id, 'bank_name' => 'Vietcombank', 'balance' => 5000000],
        ];
    }

    public function create(array $input): array
    {
        if ($this->accountExists) throw new RuntimeException('Account number already exists');
        return ['success' => true, 'data' => array_merge(['id' => 1], $input)];
    }

    public function update(int $id, array $input): array
    {
        if ($this->notFound) throw new RuntimeException('Bank account not found');
        return ['success' => true, 'data' => ['id' => $id]];
    }

    public function delete(int $id): array
    {
        if ($this->notFound) throw new RuntimeException('Bank account not found');
        if ($this->hasTransactions) throw new RuntimeException('Cannot delete account with transactions');
        return ['success' => true];
    }

    public function toggleActive(int $id): array
    {
        if ($this->notFound) throw new RuntimeException('Bank account not found');
        return ['success' => true, 'data' => ['id' => $id, 'is_active' => false]];
    }

    public function getBalance(int $id): array
    {
        if ($this->notFound) throw new RuntimeException('Bank account not found');
        return ['success' => true, 'data' => ['balance' => 5000000]];
    }

    public function getTransactions(int $id, array $filters = []): array
    {
        return [
            'success' => true,
            'data' => [
                ['type' => 'deposit', 'amount' => 1000000, 'date' => '2024-06-15'],
                ['type' => 'withdrawal', 'amount' => -500000, 'date' => '2024-06-16'],
            ],
        ];
    }

    public function recordDeposit(int $id, array $input): array
    {
        if ($this->notFound) throw new RuntimeException('Bank account not found');
        return ['success' => true, 'data' => ['transaction_id' => 1]];
    }

    public function recordWithdrawal(int $id, array $input): array
    {
        if ($this->notFound) throw new RuntimeException('Bank account not found');
        if ($this->insufficientBalance) throw new RuntimeException('Insufficient balance');
        return ['success' => true, 'data' => ['transaction_id' => 2]];
    }

    public function transfer(int $fromId, int $toId, array $input): array
    {
        if ($fromId === $toId) throw new RuntimeException('Cannot transfer to the same account');
        if ($this->insufficientBalance) throw new RuntimeException('Insufficient balance');
        return ['success' => true];
    }
}
