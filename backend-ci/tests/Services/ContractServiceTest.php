<?php

namespace Tests\Services;

use App\Services\Contracts\ContractService;
use App\Repositories\Contracts\ContractRepository;
use App\Repositories\Contracts\ContractTemplateRepository;
use App\Validators\ContractValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;
use InvalidArgumentException;

class ContractServiceTest extends CIUnitTestCase
{
    private ContractService $service;
    private ContractFakeRepo $repo;
    private ContractTemplateFakeRepo $templateRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ContractFakeRepo();
        $this->templateRepo = new ContractTemplateFakeRepo();
        $validator = new class extends ContractValidator {
            public function validate(array $data): array
            {
                return array_merge([
                    'template_id' => null,
                    'terms' => [],
                ], $data);
            }
        };
        $this->service = new ContractService($this->repo, $this->templateRepo, $validator);
    }

    public function testCreateSuccess(): void
    {
        $data = ['name' => 'Test Contract'];
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testCreateWithTemplate(): void
    {
        $data = ['name' => 'Test', 'template_id' => 1];
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
    }

    public function testCreateThrowsWhenTemplateNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Contract template not found');
        
        $this->service->create(['name' => 'Test', 'template_id' => 999]);
    }

    public function testGetReturnsContract(): void
    {
        $result = $this->service->get(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Contract not found');
        
        $this->service->get(999);
    }

    public function testActivateChangesStatus(): void
    {
        $result = $this->service->activate(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('active', $result['data']['status']);
    }

    public function testActivateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->activate(999);
    }

    public function testCloseChangesStatus(): void
    {
        $result = $this->service->close(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('closed', $result['data']['status']);
    }

    public function testCloseThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->close(999);
    }

    public function testCreateTemplateSuccess(): void
    {
        $result = $this->service->createTemplate(['name' => 'New Template']);
        
        $this->assertTrue($result['success']);
    }

    public function testCreateTemplateThrowsWhenNameEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name is required');
        
        $this->service->createTemplate(['name' => '']);
    }

    public function testGetTemplateReturnsTemplate(): void
    {
        $result = $this->service->getTemplate(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['data']['id']);
    }

    public function testGetTemplateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Contract template not found');
        
        $this->service->getTemplate(999);
    }

    public function testRenewReturnsMessage(): void
    {
        $result = $this->service->renew(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Renewal workflow pending', $result['message']);
    }

    public function testRenewThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        
        $this->service->renew(999);
    }
}

class ContractFakeRepo extends ContractRepository
{
    private array $contractData = [
        1 => ['id' => 1, 'name' => 'Contract 1', 'status' => 'draft'],
    ];

    public function __construct() {}

    public function findById(int $id): ?array
    {
        return $this->contractData[$id] ?? null;
    }

    public function create(array $data, array $terms = []): array
    {
        $id = max(array_keys($this->contractData) ?: [0]) + 1;
        $this->contractData[$id] = array_merge($data, ['id' => $id, 'status' => 'draft']);
        return $this->contractData[$id];
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (isset($this->contractData[$id])) {
            $this->contractData[$id]['status'] = $status;
            return true;
        }
        return false;
    }
}

class ContractTemplateFakeRepo extends ContractTemplateRepository
{
    private array $templateData = [
        1 => ['id' => 1, 'name' => 'Standard', 'terms' => null, 'status' => 'active'],
    ];

    public function __construct() {}

    public function findById(int $id): ?array
    {
        return $this->templateData[$id] ?? null;
    }

    public function create(array $data): array
    {
        $id = max(array_keys($this->templateData) ?: [0]) + 1;
        $this->templateData[$id] = array_merge($data, ['id' => $id]);
        return $this->templateData[$id];
    }
}
