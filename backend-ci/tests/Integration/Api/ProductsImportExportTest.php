<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\CompleteSchemaTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Products import/export API
 * @agent-pattern: Integration test with file upload/download
 */
class ProductsImportExportTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->setUpAuthToken();
        $_FILES = [];
    }

    protected function tearDown(): void
    {
        $_FILES = [];
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_import_valid_excel(): void
    {
        $file = $this->makeSheet([['PC1', 'Prod C1', 123000, 5, 'Cat', 'active']]);
        $this->mockUpload($file, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $res = $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->post('api/products/import');

        $body = $this->decodeResponse($res);
        $res->assertStatus(200);
        $this->assertTrue($body['success'] ?? false, json_encode($body));
        $this->assertEquals(1, $body['imported'] ?? null);

        $row = $this->db->table('products')->where('code', 'PC1')->get()->getRowArray();
        $this->assertNotEmpty($row);
    }

    public function test_import_missing_file_returns_400(): void
    {
        $_FILES = [];
        $res = $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->post('api/products/import');

        $res->assertStatus(400);
    }

    public function test_import_rejects_wrong_extension(): void
    {
        $path = $this->makeTempFile('.txt', 'dummy');
        $this->mockUpload($path, 'text/plain');

        $res = $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->post('api/products/import');

        $res->assertStatus(400);
    }

    public function test_import_rejects_large_file(): void
    {
        $path = $this->makeTempFile('.xlsx', str_repeat('A', 6 * 1024 * 1024));
        $this->mockUpload($path, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        $res = $this->withHeaders($this->authHeaders(['Accept' => 'application/json']))
            ->post('api/products/import');

        $res->assertStatus(400);
    }

    public function test_export_downloads_excel(): void
    {
        $service = service('productService');
        $service->create([
            'code' => 'EXP1',
            'name' => 'Export 1',
            'selling_price' => 111000,
            'stock_quantity' => 3,
            'status' => 'active',
        ]);

        $res = $this->withHeaders($this->authHeaders())
            ->get('api/products/export');

        $res->assertStatus(200);
        $resp = $res->response();
        $contentType = (string) $resp->getHeaderLine('Content-Type');
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            $contentType
        );
        $this->assertNotEmpty((string) $res->getBody());
    }

    public function test_download_import_template(): void
    {
        $res = $this->withHeaders($this->authHeaders())
            ->get('api/products/import/template');

        $res->assertStatus(200);
        $resp = $res->response();
        $this->assertStringContainsString(
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            (string) $resp->getHeaderLine('Content-Type')
        );
        $this->assertNotEmpty((string) $res->getBody());
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
        $path = tempnam($dir, 'import_api_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        return $path;
    }

    private function mockUpload(string $path, string $mime): void
    {
        $_FILES = [
            'file' => [
                'name' => basename($path),
                'type' => $mime,
                'tmp_name' => $path,
                'error' => UPLOAD_ERR_OK,
                'size' => filesize($path),
            ],
        ];
    }

    private function decodeResponse($res): array
    {
        $body = trim(strip_tags((string) $res->getBody()));
        $json = json_decode($body, true);
        return is_array($json) ? $json : ['__raw' => $body];
    }

    private function makeTempFile(string $suffix, string $content): string
    {
        $dir = WRITEPATH . 'tests';
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $path = tempnam($dir, 'file_') . $suffix;
        file_put_contents($path, $content);
        return $path;
    }
}
