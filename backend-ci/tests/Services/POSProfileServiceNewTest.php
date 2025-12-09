<?php

namespace Tests\Services;

use App\Services\POS\POSProfileService;
use App\Repositories\POS\POSProfileRepository;
use App\Repositories\PaymentMethods\PaymentMethodRepository;
use App\Validators\POSProfileValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;
use InvalidArgumentException;

class POSProfileServiceNewTest extends CIUnitTestCase
{
    private POSProfileService $service;
    private POSProfileFakeRepo $profileRepo;
    private PaymentMethodFakeRepoForPOS $paymentRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profileRepo = new POSProfileFakeRepo();
        $this->paymentRepo = new PaymentMethodFakeRepoForPOS();
        $validator = new class extends POSProfileValidator {
            public function validateCreate(array $data): array
            {
                return array_merge([
                    'name' => $data['name'] ?? 'Default',
                    'branch_id' => $data['branch_id'] ?? 1,
                    'payment_methods' => $data['payment_methods'] ?? [],
                ], $data);
            }
            public function validateUpdate(array $data): array
            {
                return array_merge(['payment_methods' => null], $data);
            }
            public function validateResolve(array $data): array
            {
                return [
                    'profile_id' => $data['profile_id'] ?? null,
                    'user_id' => $data['user_id'] ?? null,
                    'branch_id' => $data['branch_id'] ?? null,
                ];
            }
        };
        $this->service = new POSProfileService($this->profileRepo, $validator, $this->paymentRepo);
    }

    public function testCreateSuccess(): void
    {
        $data = [
            'name' => 'Main POS',
            'branch_id' => 1,
            'payment_methods' => ['CASH'],
        ];
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testCreateThrowsOnInvalidPaymentMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment method INVALID not found');
        
        $this->service->create([
            'name' => 'Test',
            'payment_methods' => ['INVALID'],
        ]);
    }

    public function testCreateThrowsOnEmptyPaymentMethodCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('payment_method code is required');
        
        $this->service->create([
            'name' => 'Test',
            'payment_methods' => [''],
        ]);
    }

    public function testUpdateSuccess(): void
    {
        $result = $this->service->update(1, ['name' => 'Updated']);
        
        $this->assertTrue($result['success']);
    }

    public function testUpdateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('POS profile not found');
        
        $this->service->update(999, ['name' => 'Test']);
    }

    public function testUpdateWithPaymentMethods(): void
    {
        $result = $this->service->update(1, [
            'name' => 'Updated',
            'payment_methods' => ['CASH', 'TRANSFER'],
        ]);
        
        $this->assertTrue($result['success']);
    }

    public function testUpdateThrowsOnInvalidPaymentMethod(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->service->update(1, ['payment_methods' => ['INVALID']]);
    }

    public function testShowReturnsProfile(): void
    {
        $result = $this->service->show(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testShowThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('POS profile not found');
        
        $this->service->show(999);
    }

    public function testResolveByProfileId(): void
    {
        $result = $this->service->resolve(['profile_id' => 1]);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testResolveByUserId(): void
    {
        $result = $this->service->resolve(['user_id' => 1, 'branch_id' => 1]);
        
        $this->assertTrue($result['success']);
        $this->assertNotNull($result['data']);
    }

    public function testResolveReturnsNullWhenNotFound(): void
    {
        $result = $this->service->resolve(['user_id' => 999]);
        
        $this->assertTrue($result['success']);
        $this->assertNull($result['data']);
    }

    public function testCreateWithPaymentMethodArray(): void
    {
        $data = [
            'name' => 'Test',
            'payment_methods' => [
                ['payment_method' => 'CASH'],
                ['payment_method' => 'TRANSFER'],
            ],
        ];
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
    }
}

class POSProfileFakeRepo extends POSProfileRepository
{
    private array $profileData = [
        1 => ['id' => 1, 'name' => 'Main', 'branch_id' => 1],
        2 => ['id' => 2, 'name' => 'Secondary', 'branch_id' => 2],
    ];

    public function __construct() {}

    public function findById(int $id): ?array
    {
        return $this->profileData[$id] ?? null;
    }

    public function create(array $data, array $paymentMethods = []): array
    {
        $id = max(array_keys($this->profileData)) + 1;
        $this->profileData[$id] = array_merge($data, ['id' => $id]);
        return $this->profileData[$id];
    }

    public function update(int $id, array $data, ?array $paymentMethods = null): array
    {
        if (isset($this->profileData[$id])) {
            $this->profileData[$id] = array_merge($this->profileData[$id], $data);
        }
        return $this->profileData[$id] ?? [];
    }

    public function findForUser(int $userId, ?int $branchId = null): ?array
    {
        if ($userId === 999) {
            return null;
        }
        return $this->profileData[1] ?? null;
    }
}

class PaymentMethodFakeRepoForPOS extends PaymentMethodRepository
{
    private array $methodData = [
        'CASH' => ['id' => 1, 'code' => 'CASH', 'name' => 'Cash'],
        'TRANSFER' => ['id' => 2, 'code' => 'TRANSFER', 'name' => 'Transfer'],
    ];

    public function __construct() {}

    public function findByCode(string $code): ?array
    {
        return $this->methodData[$code] ?? null;
    }
}
