<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use XMLReader;
use ZipArchive;

/**
 * Import invoices from KiotViet XLSX export file using streaming XML parser.
 * 
 * This seeder reads the XLSX file directly as a ZIP, then streams the XML
 * to avoid loading the entire file into memory (required for large files).
 * 
 * XLSX file location: archive/kiotviet-export/DanhSachHoaDon_KV*.xlsx
 * 
 * Run: php spark db:seed ImportInvoicesFromKiotViet
 * 
 * @agent-seeder: Import invoices from KiotViet XLSX (streaming)
 * @agent-pattern: XLSX streaming import via ZIP XML extraction
 */
class ImportInvoicesFromKiotViet extends Seeder
{
    // KiotViet column mapping (A=0, B=1, etc.)
    private const COLUMNS = [
        'A' => 'invoice_code',       // Mã hóa đơn
        'B' => 'shipment_code',      // Mã vận đơn
        'C' => 'delivery_status',    // Trạng thái giao hàng
        'D' => 'reconciliation',     // Mã đối soát
        'E' => 'issue_time',         // Thời gian
        'F' => 'created_time',       // Thời gian tạo
        'G' => 'updated_time',       // Ngày cập nhật
        'H' => 'order_code',         // Mã đặt hàng
        'I' => 'return_code',        // Mã trả hàng
        'J' => 'warranty_code',      // Mã YCSC
        'K' => 'customer_code',      // Mã KH
        'L' => 'customer_name',      // Khách hàng
        'M' => 'email',              // Email
        'N' => 'phone',              // Điện thoại
        'O' => 'address',            // Địa chỉ (Khách hàng)
        'P' => 'region',             // Khu vực
        'Q' => 'ward',               // Phường/Xã
        'R' => 'dob',                // Ngày sinh
        'S' => 'branch',             // Chi nhánh
        'T' => 'seller',             // Người bán
        'U' => 'creator',            // Người tạo
        'V' => 'channel',            // Kênh bán
        'W' => 'shipping_partner',   // Đối tác giao hàng
        'X' => 'note',               // Ghi chú
        'Y' => 'goods_total',        // Tổng tiền hàng
        'Z' => 'discount',           // Giảm giá
        'AA' => 'net_total',         // Tổng sau giảm giá
        'AB' => 'tax_discount',      // Giảm thuế
        'AC' => 'other_fee',         // Thu khác
        'AD' => 'customer_payable',  // Khách cần trả
        'AE' => 'customer_paid',     // Khách đã trả
        'AF' => 'payment_discount',  // Chiết khấu thanh toán
        'AG' => 'cod_amount',        // Còn cần thu (COD)
        'AH' => 'shipping_fee',      // Phí trả ĐTGH
        'AI' => 'delivery_note',     // Ghi chú trạng thái giao hàng
        'AJ' => 'delivery_time',     // Thời gian giao hàng
        'AK' => 'status',            // Trạng thái
    ];

    private array $customerMap = [];
    private array $branchMap = [];
    private array $userMap = [];
    private array $existingCodes = [];

    public function run(): void
    {
        // Find the XLSX file
        $exportDir = ROOTPATH . 'archive/kiotviet-export/';
        $files = glob($exportDir . 'DanhSachHoaDon_KV*.xlsx');
        
        if (empty($files)) {
            echo "❌ Invoice XLSX file not found in {$exportDir}\n";
            return;
        }
        
        $xlsxFile = $files[0];
        echo "→ Importing invoices from: " . basename($xlsxFile) . "\n";
        
        // Build lookup maps
        $this->customerMap = $this->buildCustomerMap();
        $this->branchMap = $this->buildBranchMap();
        $this->userMap = $this->buildUserMap();
        $this->existingCodes = $this->getExistingInvoiceCodes();
        
        echo "   Customer map: " . count($this->customerMap) . " entries\n";
        echo "   Branch map: " . count($this->branchMap) . " entries\n";
        echo "   Existing invoices: " . count($this->existingCodes) . "\n";
        
        // Extract XML from XLSX (which is a ZIP file)
        $zip = new ZipArchive();
        if ($zip->open($xlsxFile) !== true) {
            echo "❌ Cannot open XLSX as ZIP\n";
            return;
        }
        
        // Try both sheet1.xml and sheet2.xml
        $sheetFile = 'xl/worksheets/sheet1.xml';
        if ($zip->locateName($sheetFile) === false) {
            $sheetFile = 'xl/worksheets/sheet2.xml';
        }
        
        // Extract to temp file
        $tempDir = sys_get_temp_dir();
        $tempXml = $tempDir . '/invoice_sheet_' . time() . '.xml';
        
        echo "   Extracting: $sheetFile\n";
        $content = $zip->getFromName($sheetFile);
        $zip->close();
        
        if (!$content) {
            echo "❌ Cannot read sheet XML from XLSX\n";
            return;
        }
        
        file_put_contents($tempXml, $content);
        unset($content); // Free memory
        
        echo "   Extracted " . round(filesize($tempXml) / 1024 / 1024, 2) . " MB XML\n";
        
        // Stream parse the XML
        $this->parseSheetXml($tempXml);
        
        // Cleanup
        @unlink($tempXml);
        
        echo "✅ Import complete!\n";
    }
    
    private function parseSheetXml(string $xmlPath): void
    {
        $reader = new XMLReader();
        if (!$reader->open($xmlPath)) {
            echo "❌ Cannot open XML for reading\n";
            return;
        }
        
        $batch = [];
        $batchSize = 500;
        $imported = 0;
        $skipped = 0;
        $rowNum = 0;
        $now = date('Y-m-d H:i:s');
        
        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                continue;
            }
            
            $rowNum++;
            
            // Skip header row (row 1)
            $rowAttr = $reader->getAttribute('r');
            if ($rowAttr === '1') {
                continue;
            }
            
            // Read entire row element
            $rowXml = $reader->readOuterXml();
            $rowData = $this->parseRowXml($rowXml);
            
            $invoiceCode = trim($rowData['invoice_code'] ?? '');
            if (empty($invoiceCode)) {
                $skipped++;
                continue;
            }
            
            // Skip if already exists
            if (isset($this->existingCodes[$invoiceCode])) {
                $skipped++;
                continue;
            }
            
            $customerCode = trim($rowData['customer_code'] ?? '');
            $customerId = $this->customerMap[$customerCode] ?? null;
            
            $branchName = trim($rowData['branch'] ?? '');
            $branchId = $this->branchMap[$branchName] ?? 1;
            
            $creatorName = trim($rowData['creator'] ?? '');
            $createdBy = $this->userMap[$creatorName] ?? 1;
            
            $batch[] = [
                'invoice_number' => $invoiceCode,
                'shipment_code' => $this->cleanStr($rowData['shipment_code'] ?? null),
                'delivery_status' => $this->mapDeliveryStatus($rowData['delivery_status'] ?? null),
                'invoice_status' => $this->mapInvoiceStatus($rowData['status'] ?? null),
                'invoice_type' => 'standard',
                'customer_id' => $customerId,
                'branch_id' => $branchId,
                'issue_date' => $this->parseExcelDate($rowData['issue_time'] ?? null),
                'sales_channel' => $this->cleanStr($rowData['channel'] ?? null),
                'shipping_partner' => $this->cleanStr($rowData['shipping_partner'] ?? null),
                'notes' => $this->cleanStr($rowData['note'] ?? null),
                'goods_total' => $this->parseNumber($rowData['goods_total'] ?? null),
                'discount_total' => $this->parseNumber($rowData['discount'] ?? null),
                'net_total' => $this->parseNumber($rowData['net_total'] ?? null),
                'subtotal' => $this->parseNumber($rowData['net_total'] ?? null),
                'tax_discount' => $this->parseNumber($rowData['tax_discount'] ?? null),
                'other_fee' => $this->parseNumber($rowData['other_fee'] ?? null),
                'customer_payable' => $this->parseNumber($rowData['customer_payable'] ?? null),
                'customer_paid' => $this->parseNumber($rowData['customer_paid'] ?? null),
                'payment_discount' => $this->parseNumber($rowData['payment_discount'] ?? null),
                'cod_amount' => $this->parseNumber($rowData['cod_amount'] ?? null),
                'shipping_fee' => $this->parseNumber($rowData['shipping_fee'] ?? null),
                'total' => $this->parseNumber($rowData['customer_payable'] ?? null),
                'delivery_note' => $this->cleanStr($rowData['delivery_note'] ?? null),
                'delivery_time' => $this->parseExcelDate($rowData['delivery_time'] ?? null),
                'created_by' => $createdBy,
                'created_at' => $this->parseExcelDate($rowData['created_time'] ?? null) ?: $now,
                'updated_at' => $this->parseExcelDate($rowData['updated_time'] ?? null) ?: $now,
            ];
            
            if (count($batch) >= $batchSize) {
                $this->db->table('invoices')->insertBatch($batch);
                $imported += count($batch);
                echo "   Imported {$imported}... (skipped: {$skipped})\n";
                $batch = [];
            }
        }
        
        $reader->close();
        
        // Insert remaining batch
        if (!empty($batch)) {
            $this->db->table('invoices')->insertBatch($batch);
            $imported += count($batch);
        }
        
        echo "   Total imported: {$imported} (skipped: {$skipped})\n";
    }
    
    private function parseRowXml(string $xml): array
    {
        $data = [];
        
        // Parse cells: <c r="A2" t="str"><v>VALUE</v></c>
        preg_match_all('/<c r="([A-Z]+)\d+"[^>]*>(?:<v>([^<]*)<\/v>)?/s', $xml, $matches, PREG_SET_ORDER);
        
        foreach ($matches as $match) {
            $col = $match[1];
            $value = $match[2] ?? '';
            
            if (isset(self::COLUMNS[$col])) {
                $data[self::COLUMNS[$col]] = $value;
            }
        }
        
        return $data;
    }
    
    private function buildCustomerMap(): array
    {
        $map = [];
        if (!$this->db->tableExists('customers')) {
            return $map;
        }
        $rows = $this->db->table('customers')->select('id, code')->get()->getResultArray();
        foreach ($rows as $row) {
            if (!empty($row['code'])) {
                $map[trim($row['code'])] = (int) $row['id'];
            }
        }
        return $map;
    }
    
    private function buildBranchMap(): array
    {
        $map = [];
        if (!$this->db->tableExists('branches')) {
            return $map;
        }
        $rows = $this->db->table('branches')->select('id, name')->get()->getResultArray();
        foreach ($rows as $row) {
            if (!empty($row['name'])) {
                $map[trim($row['name'])] = (int) $row['id'];
            }
        }
        return $map;
    }
    
    private function buildUserMap(): array
    {
        $map = [];
        if (!$this->db->tableExists('users')) {
            return $map;
        }
        $rows = $this->db->table('users')->select('id, full_name')->get()->getResultArray();
        foreach ($rows as $row) {
            if (!empty($row['full_name'])) {
                $map[trim($row['full_name'])] = (int) $row['id'];
            }
        }
        return $map;
    }
    
    private function getExistingInvoiceCodes(): array
    {
        $codes = [];
        $rows = $this->db->table('invoices')->select('invoice_number')->get()->getResultArray();
        foreach ($rows as $row) {
            if (!empty($row['invoice_number'])) {
                $codes[trim($row['invoice_number'])] = true;
            }
        }
        return $codes;
    }
    
    private function cleanStr($val): ?string
    {
        if ($val === null || $val === '') {
            return null;
        }
        return trim((string) $val);
    }
    
    private function parseNumber($val): float
    {
        if ($val === null || $val === '') {
            return 0.0;
        }
        return (float) preg_replace('/[^0-9.\-]/', '', (string) $val);
    }
    
    private function parseExcelDate($val): ?string
    {
        if (empty($val)) {
            return null;
        }
        
        // If it's a numeric value, it's Excel serial date
        if (is_numeric($val)) {
            // Excel date serial number to Unix timestamp
            // Excel dates start from 1900-01-01
            $unixDate = ($val - 25569) * 86400;
            return date('Y-m-d H:i:s', (int) $unixDate);
        }
        
        // Try parsing as string date
        $time = strtotime((string) $val);
        if ($time !== false) {
            return date('Y-m-d H:i:s', $time);
        }
        
        return null;
    }
    
    private function mapDeliveryStatus(?string $status): ?string
    {
        $status = trim($status ?? '');
        if (empty($status)) return null;
        
        $map = [
            'Đang xử lý' => 'pending',
            'Chờ xử lý' => 'pending',
            'Đang giao hàng' => 'shipping',
            'Đang giao' => 'shipping',
            'Giao thành công' => 'delivered',
            'Đã giao' => 'delivered',
            'Giao thất bại' => 'failed',
            'Không giao được' => 'failed',
            'Đang hoàn' => 'returning',
            'Hoàn hàng' => 'returning',
        ];
        return $map[$status] ?? null;
    }
    
    private function mapInvoiceStatus(?string $status): string
    {
        $status = trim($status ?? '');
        $map = [
            'Hoàn thành' => 'completed',
            'Đã hoàn thành' => 'completed',
            'Đang xử lý' => 'processing',
            'Chờ xử lý' => 'processing',
            'Đã hủy' => 'cancelled',
            'Hủy' => 'cancelled',
        ];
        return $map[$status] ?? 'processing';
    }
}
