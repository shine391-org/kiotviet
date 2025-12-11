<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use XMLReader;
use ZipArchive;

/**
 * Fix missing customer_ids in invoices by re-reading KiotViet export.
 * Run: php spark db:seed FixInvoiceCustomerIds
 */
class FixInvoiceCustomerIds extends Seeder
{
    private const COLUMNS = [
        'A' => 'invoice_code',
        'K' => 'customer_code',
        'L' => 'customer_name',
    ];

    public function run(): void
    {
        // 1. Get List of Invoices with NULL customer_id
        $nullInvoices = $this->db->table('invoices')
            ->select('id, invoice_number')
            ->where('customer_id', null)
            ->orWhere('customer_id', 0)
            ->get()
            ->getResultArray();

        if (empty($nullInvoices)) {
            echo "✅ No invoices with missing customer_id found.\n";
            return;
        }

        $targetMap = []; // invoice_code => invoice_id
        foreach ($nullInvoices as $row) {
            $targetMap[trim($row['invoice_number'])] = $row['id'];
        }

        echo "→ Found " . count($targetMap) . " invoices to fix.\n";

        // 2. Build Customer Map
        echo "→ Building Customer Map...\n";
        $customerMap = [];
        $rows = $this->db->table('customers')->select('id, code')->get()->getResultArray();
        foreach ($rows as $row) {
            $customerMap[trim($row['code'])] = $row['id'];
        }
        echo "   Map size: " . count($customerMap) . "\n";

        // 3. Find XLSX File
        $exportDir = ROOTPATH . 'archive/kiotviet-export/';
        $files = glob($exportDir . 'DanhSachHoaDon_KV*.xlsx');
        
        if (empty($files)) {
            echo "❌ Invoice XLSX file not found.\n";
            return;
        }
        $xlsxFile = $files[0];
        echo "→ Reading: " . basename($xlsxFile) . "\n";

        // 4. Stream Parse and Update
        $zip = new ZipArchive();
        if ($zip->open($xlsxFile) !== true) die("❌ Cannot open ZIP\n");
        
        $sheetFile = 'xl/worksheets/sheet1.xml';
        if ($zip->locateName($sheetFile) === false) $sheetFile = 'xl/worksheets/sheet2.xml';
        
        $tempDir = sys_get_temp_dir();
        $tempXml = $tempDir . '/fix_inv_cust_' . time() . '.xml';
        file_put_contents($tempXml, $zip->getFromName($sheetFile));
        $zip->close();

        $reader = new XMLReader();
        $reader->open($tempXml);

        $fixed = 0;
        $batchFull = [];
        $batchName = [];

        while ($reader->read()) {
            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') continue;

            // Skip header
            if ($reader->getAttribute('r') === '1') continue;

            $rowXml = $reader->readOuterXml();
            
            // Extract only needed columns (A and K)
            $invCode = $this->extractVal($rowXml, 'A');
            
            if (empty($invCode) || !isset($targetMap[$invCode])) {
                continue;
            }

            $custCode = $this->extractVal($rowXml, 'K');
            $custName = $this->extractVal($rowXml, 'L'); // Column L is customer name

            $custId = null;
            
            // 1. Try exact match
            if (!empty($custCode) && isset($customerMap[$custCode])) {
                $custId = $customerMap[$custCode];
            }
            // 2. Try stripping {DEL} suffix
            elseif (!empty($custCode) && str_contains($custCode, '{DEL}')) {
                $cleanCode = str_replace('{DEL}', '', $custCode);
                if (isset($customerMap[$cleanCode])) {
                    $custId = $customerMap[$cleanCode];
                }
            }

            if ($custId) {
                $batchFull[] = [
                    'id' => $targetMap[$invCode],
                    'customer_id' => $custId,
                    'customer_name' => $custName ?: $custCode
                ];
                $fixed++;
                unset($targetMap[$invCode]);
            } else {
                if (!empty($custName) || !empty($custCode)) {
                    $batchName[] = [
                        'id' => $targetMap[$invCode],
                        'customer_name' => $custName ?: $custCode
                    ];
                }

                if (!isset($missingCodes[$custCode])) $missingCodes[$custCode] = 0;
                $missingCodes[$custCode]++;
            }

            if (count($batchFull) >= 100) {
                $this->db->table('invoices')->updateBatch($batchFull, 'id');
                echo "   Updated Full: " . count($batchFull) . "...\n";
                $batchFull = [];
            }
            if (count($batchName) >= 100) {
                $this->db->table('invoices')->updateBatch($batchName, 'id');
                echo "   Updated Name: " . count($batchName) . "...\n";
                $batchName = [];
            }
        }
        
        if (!empty($batchFull)) {
            $this->db->table('invoices')->updateBatch($batchFull, 'id');
        }
        if (!empty($batchName)) {
            $this->db->table('invoices')->updateBatch($batchName, 'id');
        }

        $reader->close();
        @unlink($tempXml);

        echo "✅ Fixed total: {$fixed} invoices.\n";
        
        if (!empty($missingCodes)) {
             echo "⚠️ Missing Customer Codes in DB:\n";
             foreach ($missingCodes as $code => $count) {
                 echo "   - $code: $count invoices\n";
                 if ($count > 50) break; // limit output
             }
        }
    }

    private function extractVal($xml, $colLetter)
    {
        // Simple regex to find cell value
        // <c r="K2" ...><v>KH001</v></c> or <c r="K2" t="s"><v>123</v></c> (shared string - unsupported here?)
        // Wait, KiotViet export usually uses inline strings (t="str") or shared strings?
        // ImportInvoicesFromKiotViet used logic: <c r="([A-Z]+)\d+"[^>]*>(?:<v>([^<]*)<\/v>)?
        // I will use same regex logic
        
        if (preg_match('/<c r="' . $colLetter . '\d+"[^>]*>(?:<v>([^<]*)<\/v>)?/s', $xml, $matches)) {
            return $matches[1] ?? '';
        }
        return '';
    }
}
