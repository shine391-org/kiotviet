<?php

namespace Tests\Services;

use App\Services\Products\ProductImportService;
use CodeIgniter\Test\CIUnitTestCase;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * @agent-test: Product import template
 * @agent-pattern: Template generation test
 */
class ProductImportTemplateTest extends CIUnitTestCase
{
    /** @test */
    public function it_generates_template_with_headers_and_sample()
    {
        $service = service('productImportService');
        $path = $service->generateTemplate();
        $sheet = IOFactory::load($path)->getActiveSheet();

        $this->assertEquals('code', $sheet->getCell('A1')->getValue());
        $this->assertEquals('name', $sheet->getCell('B1')->getValue());
        $this->assertEquals('P001', $sheet->getCell('A2')->getValue());
        @unlink($path);
    }
}
