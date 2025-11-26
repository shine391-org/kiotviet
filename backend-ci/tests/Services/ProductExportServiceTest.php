<?php

namespace Tests\Services;

use App\Services\Products\ProductExportService;
use App\Services\Products\ProductService;
use CodeIgniter\Test\CIUnitTestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: ProductExportService
 * @agent-pattern: MySQL-only test with DevDatabaseTrait
 */
class ProductExportServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private ProductExportService $service;
    private \App\Services\Products\ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = service('productExportService');
        $this->productService = service('productService');
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_exports_header_when_no_products(): void
    {
        $path = $this->service->exportToExcel([]);
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertEquals(1, $sheet->getHighestDataRow());
        @unlink($path);
    }

    /** @test */
    public function it_exports_products_rows(): void
    {
        $this->seedProduct(['code' => 'PX1', 'name' => 'One', 'selling_price' => 100, 'stock_quantity' => 5, 'status' => 'active']);
        $this->seedProduct(['code' => 'PX2', 'name' => 'Two', 'selling_price' => 200, 'stock_quantity' => 10, 'status' => 'inactive']);

        $path = $this->service->exportToExcel([]);
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertEquals(3, $sheet->getHighestDataRow());
        $this->assertEquals('PX1', $sheet->getCell('A2')->getValue());
        $this->assertEquals('PX2', $sheet->getCell('A3')->getValue());
        @unlink($path);
    }

    /** @test */
    public function it_applies_filters_on_export(): void
    {
        $this->seedProduct(['code' => 'ACTIVE', 'name' => 'Active', 'status' => 'active', 'selling_price' => 10]);
        $this->seedProduct(['code' => 'INACTIVE', 'name' => 'Inactive', 'status' => 'inactive', 'selling_price' => 20]);

        $path = $this->service->exportToExcel(['status' => 'inactive', 'page' => 1, 'limit' => 50]);
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertEquals(2, $sheet->getHighestDataRow());
        $this->assertEquals('INACTIVE', $sheet->getCell('A2')->getValue());
        @unlink($path);
    }

    private function seedProduct(array $data): void
    {
        $now = date('Y-m-d H:i:s');
        $payload = array_merge([
            'product_type' => 'goods',
            'code' => 'P' . random_int(1000, 9999),
            'name' => 'Sample',
            'selling_price' => 0,
            'stock_quantity' => 0,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ], $data);

        $this->productService->create($payload);
    }
}
