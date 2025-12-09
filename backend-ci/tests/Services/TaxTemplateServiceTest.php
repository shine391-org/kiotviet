<?php

namespace Tests\Services;

use App\Services\Taxes\TaxTemplateService;
use App\Repositories\Taxes\TaxTemplateRepository;
use App\Repositories\Taxes\TaxTemplateItemRepository;
use App\Validators\TaxTemplateValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;

class TaxTemplateServiceTest extends CIUnitTestCase
{
    private TaxTemplateService $service;
    private TaxTemplateFakeRepo $templates;
    private TaxTemplateItemFakeRepo $items;

    protected function setUp(): void
    {
        parent::setUp();
        $this->templates = new TaxTemplateFakeRepo();
        $this->items = new TaxTemplateItemFakeRepo();
        $validator = new class extends TaxTemplateValidator {
            public function validateCreate(array $data): array
            {
                return array_merge([
                    'name' => $data['name'] ?? 'Default Tax',
                    'rate_percent' => $data['rate_percent'] ?? 10,
                    'is_inclusive' => $data['is_inclusive'] ?? false,
                    'items' => $data['items'] ?? [],
                ], $data);
            }
        };
        $this->service = new TaxTemplateService($this->templates, $this->items, $validator);
    }

    public function testCreateTemplateWithItems(): void
    {
        $data = [
            'name' => 'VAT 10%',
            'rate_percent' => 10,
            'items' => [
                ['tax_name' => 'VAT', 'rate_percent' => 10, 'charge_type' => 'on_net_total'],
            ],
        ];
        
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('VAT 10%', $result['data']['name']);
        $this->assertArrayHasKey('items', $result['data']);
    }

    public function testCreateTemplateWithoutItems(): void
    {
        $data = [
            'name' => 'Simple Tax',
            'rate_percent' => 5,
        ];
        
        $result = $this->service->create($data);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('Simple Tax', $result['data']['name']);
    }

    public function testGetReturnsTemplateWithItems(): void
    {
        $result = $this->service->get(1);
        
        $this->assertTrue($result['success']);
        $this->assertEquals('VAT 10%', $result['data']['name']);
        $this->assertArrayHasKey('items', $result['data']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Template not found');
        
        $this->service->get(999);
    }

    public function testApplyExclusiveTax(): void
    {
        $result = $this->service->apply(1, 1000);
        
        $this->assertEquals(100.0, $result['tax_total']);
        $this->assertEquals(1100.0, $result['grand_total']);
    }

    public function testApplyInclusiveTax(): void
    {
        $result = $this->service->apply(2, 1000);
        
        $this->assertEquals(0.0, $result['tax_total']);
        $this->assertEquals(1000.0, $result['grand_total']);
    }

    public function testApplyThrowsWhenTemplateNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Template not found');
        
        $this->service->apply(999, 1000);
    }

    public function testApplyWithNoItems(): void
    {
        $result = $this->service->apply(3, 1000);
        
        $this->assertEquals(50.0, $result['tax_total']);
        $this->assertEquals(1050.0, $result['grand_total']);
    }

    public function testApplyWithZeroAmount(): void
    {
        $result = $this->service->apply(1, 0);
        
        $this->assertEquals(0.0, $result['tax_total']);
        $this->assertEquals(0.0, $result['grand_total']);
    }

    public function testApplyWithLargeAmount(): void
    {
        $result = $this->service->apply(1, 1000000);
        
        $this->assertEquals(100000.0, $result['tax_total']);
        $this->assertEquals(1100000.0, $result['grand_total']);
    }

    public function testApplyWithDecimalAmount(): void
    {
        $result = $this->service->apply(1, 99.99);
        
        $this->assertEquals(10.0, $result['tax_total']);
        $this->assertEquals(109.99, $result['grand_total']);
    }
}

class TaxTemplateFakeRepo extends TaxTemplateRepository
{
    private array $templateData = [
        1 => ['id' => 1, 'name' => 'VAT 10%', 'rate_percent' => 10, 'is_inclusive' => false],
        2 => ['id' => 2, 'name' => 'Inclusive VAT', 'rate_percent' => 10, 'is_inclusive' => true],
        3 => ['id' => 3, 'name' => 'Simple 5%', 'rate_percent' => 5, 'is_inclusive' => false],
    ];

    public function __construct() {}

    public function findById(int $id): ?array
    {
        return $this->templateData[$id] ?? null;
    }

    public function create(array $data): array
    {
        $id = max(array_keys($this->templateData)) + 1;
        $this->templateData[$id] = array_merge($data, ['id' => $id]);
        return $this->templateData[$id];
    }
}

class TaxTemplateItemFakeRepo extends TaxTemplateItemRepository
{
    private array $itemData = [
        1 => [
            ['id' => 1, 'template_id' => 1, 'tax_name' => 'VAT', 'rate_percent' => 10, 'charge_type' => 'on_net_total'],
        ],
        2 => [
            ['id' => 2, 'template_id' => 2, 'tax_name' => 'VAT Inclusive', 'rate_percent' => 10, 'charge_type' => 'on_net_total'],
        ],
    ];

    public function __construct() {}

    public function findByTemplate(int $templateId): array
    {
        return $this->itemData[$templateId] ?? [];
    }

    public function saveItems(int $templateId, array $itemsToSave): void
    {
        $this->itemData[$templateId] = $itemsToSave;
    }
}
