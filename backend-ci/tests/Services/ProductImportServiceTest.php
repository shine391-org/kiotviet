<?php

namespace Tests\Services;

use App\Services\Products\ProductImportService;
use CodeIgniter\Test\CIUnitTestCase;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: ProductImportService
 * @agent-pattern: MySQL-only test with DevDatabaseTrait
 */
class ProductImportServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private ProductImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = service('productImportService');
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_products_from_excel(): void
    {
        $file = $this->makeSheet([
            ['P001', 'Product 1', 100000, 50, 'Cat', 'active'],
        ]);

        $result = $this->service->importFromExcel($file);

        $this->assertTrue($result['success']);
        $this->assertEquals(1, $result['imported']);
        $this->assertEquals(0, $result['updated']);

        $productService = service('productService');
        $row = $productService->findByCode('P001');
        $this->assertNotNull($row);
        $this->assertEquals(100000, (float) $row['selling_price']);
    }

    /** @test */
    public function it_updates_existing_products_when_code_matches(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'code' => 'P002',
            'name' => 'Old name',
            'selling_price' => 50000,
            'stock_quantity' => 10,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $file = $this->makeSheet([
            ['P002', 'New name', 75000, 20, 'Cat', 'active'],
        ]);

        $result = $this->service->importFromExcel($file);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['updated']);

        $productService = service('productService');
        $row = $productService->findByCode('P002');
        $this->assertEquals('New name', $row['name']);
        $this->assertEquals(75000, (float) $row['selling_price']);
        $this->assertEquals(20, (float) $row['stock_quantity']);
    }

    /** @test */
    public function it_collects_errors_for_invalid_rows(): void
    {
        $file = $this->makeSheet([
            ['BAD', 'Bad row', 'not-number', 5, 'Cat', 'active'],
        ]);

        $result = $this->service->importFromExcel($file);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(1, $result['failed']);
        $this->assertCount(1, $result['errors']);
    }

    /** @test */
    public function it_skips_empty_rows(): void
    {
        $file = $this->makeSheet([
            ['', '', '', '', '', ''],
        ]);

        $result = $this->service->importFromExcel($file);

        $this->assertEquals(0, $result['imported']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEquals(0, $result['failed']);
    }

    /** @test */
    public function it_can_write_to_products_table_directly(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('products')->insert([
            'code' => 'TWRITE',
            'name' => 'Write test',
            'selling_price' => 1,
            'stock_quantity' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $row = $this->db->table('products')->get()->getRowArray();
        $this->assertNotNull($row);
        $this->assertEquals('TWRITE', $row['code']);
    }

    private function makeSheet(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['code', 'name', 'selling_price', 'stock_quantity', 'category', 'status'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . '1', $h);
        }

        $rowIndex = 2;
        foreach ($rows as $row) {
            foreach ($row as $col => $value) {
                $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col + 1);
                $sheet->setCellValue($colLetter . $rowIndex, $value);
            }
            $rowIndex++;
        }

        $dir = WRITEPATH . 'tests';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = tempnam($dir, 'import_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        return $path;
    }
}
