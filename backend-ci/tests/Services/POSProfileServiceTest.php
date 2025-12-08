<?php

namespace Tests\Services;

use App\Services\POS\POSProfileService;
use App\Repositories\POS\POSProfileRepository;
use App\Repositories\PaymentMethods\PaymentMethodRepository;
use App\Validators\POSProfileValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-test: POSProfileService (stubbed)
 * @agent-pattern: Service orchestration without DB
 */
class POSProfileServiceTest extends CIUnitTestCase
{
    private POSProfileService $service;
    private POSProfileServiceFakeRepo $repo;
    private POSProfileServiceFakePaymentRepo $paymentRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new POSProfileServiceFakeRepo();
        $this->paymentRepo = new POSProfileServiceFakePaymentRepo();
        $validator = new POSProfileValidator();
        $this->service = new POSProfileService($this->repo, $validator, $this->paymentRepo);
    }

    public function testCreateReturnsProfile(): void
    {
        $data = [
            'name' => 'Test Profile',
            'branch_id' => 1,
            'user_id' => 1,
            'payment_methods' => ['CASH', 'BANK'],
        ];

        $result = $this->service->create($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('Test Profile', $result['data']['name']);
    }

    public function testCreateValidatesPaymentMethods(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not found');

        $this->service->create([
            'name' => 'Test',
            'branch_id' => 1,
            'user_id' => 1,
            'payment_methods' => ['INVALID_METHOD'],
        ]);
    }

    public function testCreateValidatesEmptyPaymentMethodCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('payment_method is required in payment_methods');

        $this->service->create([
            'name' => 'Test',
            'branch_id' => 1,
            'user_id' => 1,
            'payment_methods' => [['payment_method' => '']],
        ]);
    }

    public function testUpdateReturnsUpdatedProfile(): void
    {
        $result = $this->service->update(1, [
            'name' => 'Updated Name',
            'payment_methods' => ['CASH'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('POS profile not found');

        $this->service->update(999, ['name' => 'Test']);
    }

    public function testShowReturnsProfile(): void
    {
        $result = $this->service->show(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['data']['id']);
        $this->assertSame('Default POS', $result['data']['name']);
    }

    public function testShowThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('POS profile not found');

        $this->service->show(999);
    }

    public function testResolveByProfileId(): void
    {
        $result = $this->service->resolve(['profile_id' => 1, 'user_id' => 1, 'branch_id' => 1]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['data']['id']);
    }

    public function testResolveByUserId(): void
    {
        $result = $this->service->resolve([
            'user_id' => 1,
            'branch_id' => 1,
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testResolveReturnsNullWhenNotFound(): void
    {
        $result = $this->service->resolve([
            'user_id' => 999,
            'branch_id' => 999,
        ]);

        $this->assertTrue($result['success']);
        $this->assertNull($result['data']);
    }
}

class POSProfileServiceFakeRepo extends POSProfileRepository
{
    private array $fakeProfiles = [];

    public function __construct()
    {
        $this->fakeProfiles = [
            1 => [
                'id' => 1,
                'name' => 'Default POS',
                'branch_id' => 1,
                'user_id' => 1,
                'is_active' => true,
                'payment_methods' => [
                    ['code' => 'CASH', 'name' => 'Cash'],
                    ['code' => 'BANK', 'name' => 'Bank Transfer'],
                ],
                'created_at' => '2024-01-01 00:00:00',
            ],
            2 => [
                'id' => 2,
                'name' => 'Branch 2 POS',
                'branch_id' => 2,
                'user_id' => 2,
                'is_active' => true,
                'payment_methods' => [
                    ['code' => 'CASH', 'name' => 'Cash'],
                ],
                'created_at' => '2024-01-02 00:00:00',
            ],
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->fakeProfiles[$id] ?? null;
    }

    public function findForUser(int $userId, ?int $branchId = null): ?array
    {
        foreach ($this->fakeProfiles as $p) {
            if ($p['user_id'] === $userId) {
                if ($branchId === null || $p['branch_id'] === $branchId) {
                    return $p;
                }
            }
        }
        return null;
    }

    public function create(array $data, array $paymentMethods = []): array
    {
        $id = max(array_keys($this->fakeProfiles)) + 1;
        $data['id'] = $id;
        $data['payment_methods'] = $paymentMethods;
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->fakeProfiles[$id] = $data;
        return $data;
    }

    public function update(int $id, array $data, ?array $paymentMethods = null): array
    {
        if (! isset($this->fakeProfiles[$id])) {
            return [];
        }
        $this->fakeProfiles[$id] = array_merge($this->fakeProfiles[$id], $data);
        if ($paymentMethods !== null) {
            $this->fakeProfiles[$id]['payment_methods'] = $paymentMethods;
        }
        return $this->fakeProfiles[$id];
    }
}

class POSProfileServiceFakePaymentRepo extends PaymentMethodRepository
{
    private array $methods = [
        'CASH' => ['id' => 1, 'code' => 'CASH', 'name' => 'Cash', 'is_active' => true],
        'BANK' => ['id' => 2, 'code' => 'BANK', 'name' => 'Bank Transfer', 'is_active' => true],
        'MOMO' => ['id' => 3, 'code' => 'MOMO', 'name' => 'MoMo Wallet', 'is_active' => true],
    ];

    public function __construct() {}

    public function findByCode(string $code): ?array
    {
        return $this->methods[strtoupper($code)] ?? null;
    }
}
