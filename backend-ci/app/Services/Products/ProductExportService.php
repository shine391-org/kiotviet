<?php

namespace App\Services\Products;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Export products to Excel.
 *
 * @agent-service: Product Export
 * @agent-pattern: List to Excel export
 * @agent-reusable: MEDIUM
 */
class ProductExportService
{
    protected ProductService $products;

    public function __construct(?ProductService $products = null)
    {
        $this->products = $products ?? service('productService');
    }

    /**
     * Export filtered products to Excel and return filepath.
     *
     * @agent-use: GET /api/products/export
     * @agent-pattern: Generate XLSX from list
     */
    public function exportToExcel(array $filters): string
    {
        $filters = array_merge(['page' => 1, 'limit' => 200, 'include_variants' => false], $filters);
        $result = $this->products->list($filters);
        $items = $result['data'] ?? [];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['code', 'name', 'selling_price', 'stock_quantity', 'category', 'status'];
        foreach ($headers as $idx => $header) {
            $sheet->setCellValue(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1) . '1', $header);
        }
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->setAutoFilter('A1:F1');

        $rowIndex = 2;
        foreach ($items as $item) {
            $sheet->setCellValue("A{$rowIndex}", $item['code'] ?? '');
            $sheet->setCellValue("B{$rowIndex}", $item['name'] ?? '');
            $sheet->setCellValue("C{$rowIndex}", $item['selling_price'] ?? 0);
            $sheet->setCellValue("D{$rowIndex}", $item['stock_quantity'] ?? 0);
            $sheet->setCellValue("E{$rowIndex}", $item['category'] ?? '');
            $sheet->setCellValue("F{$rowIndex}", $item['status'] ?? 'active');
            $rowIndex++;
        }

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
        $filepath = $dir . '/products_' . date('Ymd_His') . '.xlsx';
        (new Xlsx($spreadsheet))->save($filepath);

        return $filepath;
    }
}
