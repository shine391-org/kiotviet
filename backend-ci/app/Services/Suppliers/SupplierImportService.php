<?php

namespace App\Services\Suppliers;

use PhpOffice\PhpSpreadsheet\IOFactory;
use App\Repositories\Partners\PartnerRepository;
use CodeIgniter\I18n\Time;

/**
 * Import suppliers from Excel.
 *
 * @agent-service: Supplier Import
 * @agent-pattern: Excel to database import
 */
class SupplierImportService
{
    protected PartnerRepository $repo;

    public function __construct()
    {
        $this->repo = new PartnerRepository();
    }

    /**
     * Import suppliers from Excel file.
     * 
     * @return array{success: int, errors: array, total: int}
     */
    public function importFromExcel(string $filepath): array
    {
        $spreadsheet = IOFactory::load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        // Skip header row
        $header = array_shift($rows);
        $headerMap = $this->mapHeaders($header);

        $success = 0;
        $errors = [];
        $total = count($rows);

        foreach ($rows as $rowNum => $row) {
            $actualRow = $rowNum + 2; // +2 because we shifted header and arrays are 0-indexed
            
            try {
                $data = $this->parseRow($row, $headerMap);
                
                if (empty($data['name'])) {
                    $errors[] = "Dòng {$actualRow}: Tên nhà cung cấp không được để trống";
                    continue;
                }

                // Check for duplicate code
                if (!empty($data['code'])) {
                    $existing = $this->repo->findByCode($data['code']);
                    if ($existing) {
                        // Update existing - ensure type is set to supplier
                        $data['type'] = 'supplier';
                        $this->repo->update($existing['id'], $data);
                        $success++;
                        continue;
                    }
                }

                // Create new
                $data['type'] = 'supplier';
                $this->repo->create($data);
                $success++;

            } catch (\Throwable $e) {
                $errors[] = "Dòng {$actualRow}: " . $e->getMessage();
            }
        }

        return [
            'success' => $success,
            'errors' => $errors,
            'total' => $total,
        ];
    }

    /**
     * Get import template.
     */
    public function getTemplate(): string
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Mẫu nhập NCC');

        // Headers - only fields that exist in partners table
        $headers = [
            'Mã NCC', 'Tên nhà cung cấp (*)', 'Điện thoại', 'Email',
            'Địa chỉ', 'Thành phố', 'Mã số thuế', 'Người liên hệ'
        ];
        foreach ($headers as $idx => $header) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            $sheet->setCellValue("{$col}1", $header);
        }

        // Style header
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('4472C4');
        $sheet->getStyle('A1:H1')->getFont()->getColor()->setRGB('FFFFFF');

        // Sample data
        $sheet->setCellValue('A2', 'NCC001');
        $sheet->setCellValue('B2', 'Công ty ABC');
        $sheet->setCellValue('C2', '0901234567');
        $sheet->setCellValue('D2', 'contact@abc.com');
        $sheet->setCellValue('E2', '123 Đường ABC, Quận 1, TP.HCM');
        $sheet->setCellValue('F2', 'TP.HCM');
        $sheet->setCellValue('G2', '0123456789');
        $sheet->setCellValue('H2', 'Nguyễn Văn A');

        // Auto-size
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Add instructions sheet
        $instructionSheet = $spreadsheet->createSheet();
        $instructionSheet->setTitle('Hướng dẫn');
        $instructionSheet->setCellValue('A1', 'HƯỚNG DẪN IMPORT NHÀ CUNG CẤP');
        $instructionSheet->setCellValue('A3', '1. Cột "Tên nhà cung cấp" là bắt buộc (có dấu *)');
        $instructionSheet->setCellValue('A4', '2. Nếu "Mã NCC" trùng với mã đã có, hệ thống sẽ cập nhật thay vì tạo mới');
        $instructionSheet->setCellValue('A5', '3. Nếu để trống "Mã NCC", hệ thống sẽ tự sinh mã');
        $instructionSheet->setCellValue('A6', '4. Định dạng số điện thoại: 10-11 số');
        $instructionSheet->setCellValue('A7', '5. Mã số thuế: 10 hoặc 13 số');
        $instructionSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $spreadsheet->setActiveSheetIndex(0);

        $dir = WRITEPATH . 'exports';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $filepath = $dir . '/supplier_import_template.xlsx';
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save($filepath);

        return $filepath;
    }

    private function mapHeaders(array $header): array
    {
        $map = [];
        // Only map fields that exist in partners table:
        // id, code, name, type, contact_person, email, phone, address, city, tax_code,
        // bank_account, bank_name, credit_limit, debt_amount, total_purchased, status
        $mappings = [
            'mã ncc' => 'code',
            'mã nhà cung cấp' => 'code',
            'code' => 'code',
            'tên nhà cung cấp' => 'name',
            'tên ncc' => 'name',
            'name' => 'name',
            'điện thoại' => 'phone',
            'phone' => 'phone',
            'email' => 'email',
            'địa chỉ' => 'address',
            'address' => 'address',
            'mã số thuế' => 'tax_code',
            'mst' => 'tax_code',
            'tax_code' => 'tax_code',
            'thành phố' => 'city',
            'city' => 'city',
            'người liên hệ' => 'contact_person',
            'contact_person' => 'contact_person',
            'tài khoản ngân hàng' => 'bank_account',
            'bank_account' => 'bank_account',
            'tên ngân hàng' => 'bank_name',
            'bank_name' => 'bank_name',
        ];

        foreach ($header as $col => $value) {
            if ($value === null) continue;
            $normalized = mb_strtolower(trim(preg_replace('/\s*\(\*\)\s*/', '', $value)));
            if (isset($mappings[$normalized])) {
                $map[$col] = $mappings[$normalized];
            }
        }

        return $map;
    }

    private function parseRow(array $row, array $headerMap): array
    {
        $data = [];
        foreach ($headerMap as $col => $field) {
            $value = trim($row[$col] ?? '');
            if ($value !== '') {
                $data[$field] = $value;
            }
        }
        return $data;
    }
}
