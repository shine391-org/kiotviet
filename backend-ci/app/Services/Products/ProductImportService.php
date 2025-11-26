<?php

namespace App\Services\Products;

use App\Validators\ProductValidator;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Import products from Excel.
 *
 * @agent-service: Product Import
 * @agent-pattern: File import with validation
 * @agent-reusable: MEDIUM
 */
class ProductImportService
{
    protected ProductService $products;
    protected ProductValidator $validator;

    public function __construct(?ProductService $products = null, ?ProductValidator $validator = null)
    {
        $this->products = $products ?? service('productService');
        $this->validator = $validator ?? service('productValidator');
    }

    /**
     * Import rows from an Excel file.
     *
     * @agent-use: POST /api/products/import
     * @agent-pattern: Row-wise import with per-row validation
     */
    public function importFromExcel(string $filepath): array
    {
        if (! is_file($filepath)) {
            throw new \InvalidArgumentException('File not found for import');
        }

        $spreadsheet = IOFactory::load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestDataRow();

        $imported = 0;
        $updated = 0;
        $failed = 0;
        $errors = [];

        for ($row = 2; $row <= $highestRow; $row++) {
            $code = trim((string) $sheet->getCell('A' . $row)->getValue());
            $name = trim((string) $sheet->getCell('B' . $row)->getValue());
            $price = $sheet->getCell('C' . $row)->getCalculatedValue();
            $stock = $sheet->getCell('D' . $row)->getCalculatedValue();
            $status = trim((string) $sheet->getCell('F' . $row)->getValue());

            // Skip empty rows
            if ($code === '' && $name === '') {
                continue;
            }

            $data = [
                'code' => $code,
                'name' => $name ?: $code,
                'selling_price' => is_numeric($price) ? (float) $price : $price,
                'stock_quantity' => is_numeric($stock) ? (float) $stock : $stock,
                'status' => $status ?: 'active',
            ];

            try {
                $existing = $this->products->findByCode($code);
                if ($existing) {
                    $validated = $this->validator->validateUpdate((int) $existing['id'], $data);
                    $this->products->update((int) $existing['id'], $validated);
                    $updated++;
                } else {
                    $validated = $this->validator->validateCreate($data);
                    $this->products->create($validated);
                    $imported++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'row' => $row,
                    'code' => $code,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'imported' => $imported,
            'updated' => $updated,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Generate an empty Excel template for import.
     *
     * @agent-use: GET /api/products/import/template
     * @agent-pattern: Template export
     */
    public function generateTemplate(): string
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['code', 'name', 'selling_price', 'stock_quantity', 'category', 'status'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1) . '1', $h);
        }
        // Sample row (optional)
        $sheet->setCellValue('A2', 'P001');
        $sheet->setCellValue('B2', 'Sample Product');
        $sheet->setCellValue('C2', 100000);
        $sheet->setCellValue('D2', 10);
        $sheet->setCellValue('E2', 'Electronics');
        $sheet->setCellValue('F2', 'active');

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $dir = WRITEPATH . 'exports';
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (! is_writable($dir)) {
            @chmod($dir, 0777);
        }
        $path = $dir . '/products_import_template.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($path);
        return $path;
    }
}
