<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportKiotViet extends BaseCommand
{
    protected $group       = 'Import';
    protected $name        = 'import:kiotviet';
    protected $description = 'Import data from KiotViet Excel exports';
    protected $usage       = 'import:kiotviet [type]';
    protected $arguments   = [
        'type' => 'Type to import: products, suppliers, customers, all'
    ];

    private $basePath = WRITEPATH . 'kiotviet-export/';
    private $db;

    public function run(array $params)
    {
        $this->db = db_connect();
        $type = $params[0] ?? 'all';

        CLI::write("Starting KiotViet import: {$type}", 'green');

        switch ($type) {
            case 'products':
                $this->importProducts();
                break;
            case 'suppliers':
                $this->importSuppliers();
                break;
            case 'customers':
                $this->importCustomers();
                break;
            case 'warehouses':
                $this->importWarehouses();
                break;
            case 'delivery_partners':
                $this->importDeliveryPartners();
                break;
            case 'purchase_orders':
                $this->importPurchaseOrders();
                break;
            case 'invoices':
                $this->importInvoices();
                break;
            case 'transfers':
                $this->importStockTransfers();
                break;
            case 'audits':
                $this->importStockAudits();
                break;
            case 'disposals':
                $this->importStockDisposals();
                break;
            case 'returns':
                $this->importReturns();
                break;
            case 'attributes':
                $this->importAttributes();
                break;
            case 'pricelists':
                $this->importPriceLists();
                break;
            case 'cashbook':
                $this->importCashBook();
                break;
            case 'all':
                $this->importProducts();
                $this->importSuppliers();
                $this->importCustomers();
                $this->importDeliveryPartners();
                $this->importPurchaseOrders();
                $this->importInvoices();
                $this->importStockTransfers();
                $this->importStockAudits();
                $this->importStockDisposals();
                $this->importReturns();
                break;
            default:
                CLI::error("Unknown type: {$type}");
        }

        CLI::write("Import completed!", 'green');
    }

    private function importProducts()
    {
        CLI::write("=== Importing Products ===", 'yellow');
        $file = $this->findFile('DanhSachSanPham');
        if (!$file) {
            CLI::error("Product file not found");
            return;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        
        CLI::write("Found {$highestRow} rows", 'white');

        // Get headers
        $headers = $sheet->rangeToArray('A1:AA1', null, true, false)[0];
        
        // Map KiotViet columns to our DB
        $colMap = $this->findColumnIndexes($headers, [
            'Loại hàng' => 'product_type',
            'Nhóm hàng(3 Cấp)' => 'category',
            'Mã hàng' => 'code',
            'Tên hàng' => 'name',
            'Thương hiệu' => 'brand',
            'Giá bán' => 'selling_price',
            'Giá vốn' => 'purchase_price',
            'Tồn kho' => 'stock_quantity',
            'KH đặt' => 'customer_ordered',
            'Dự kiến hết hàng' => 'expected_out_date',
            'Tồn nhỏ nhất' => 'min_stock_alert',
            'Tồn lớn nhất' => 'max_stock_alert',
            'ĐVT' => 'unit',
            'Mã ĐVT Cơ bản' => 'base_unit_code',
            'Quy đổi' => 'unit_conversion',
            'Mã HH Liên quan' => 'related_product_codes',
            'Hình ảnh (url1,url2...)' => 'images',
            'Trọng lượng' => 'weight',
            'Đang kinh doanh' => 'is_active',
            'Được bán trực tiếp' => 'is_available_online',
            'Mô tả' => 'description',
            'Vị trí' => 'warehouse_location',
        ]);

        // Collect all categories first
        $categories = [];
        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:AA{$row}", null, true, false)[0];
            $catPath = $data[$colMap['category']] ?? '';
            if ($catPath) {
                $parts = array_map('trim', explode('>>', $catPath));
                $this->buildCategoryTree($categories, $parts);
            }
        }

        // Insert categories
        $categoryIds = $this->insertCategories($categories);
        CLI::write("Created " . count($categoryIds) . " categories", 'green');

        // Insert products
        $inserted = 0;
        $errors = 0;
        
        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:AA{$row}", null, true, false)[0];
            
            $code = trim($data[$colMap['code']] ?? '');
            if (empty($code)) continue;

            $name = trim($data[$colMap['name']] ?? '');
            $productType = $this->mapProductType($data[$colMap['product_type']] ?? '');
            $images = trim($data[$colMap['images']] ?? '');
            $imageList = $images ? explode(',', $images) : [];
            
            $product = [
                'code' => $code,
                'name' => $name,
                'slug' => $this->createSlug($name),
                'product_type' => $productType,
                'brand' => trim($data[$colMap['brand']] ?? '') ?: null,
                'selling_price' => floatval($data[$colMap['selling_price']] ?? 0),
                'purchase_price' => floatval($data[$colMap['purchase_price']] ?? 0),
                'stock_quantity' => intval($data[$colMap['stock_quantity']] ?? 0),
                'customer_ordered' => intval($data[$colMap['customer_ordered']] ?? 0),
                'expected_out_date' => trim($data[$colMap['expected_out_date']] ?? '') ?: null,
                'min_stock_alert' => intval($data[$colMap['min_stock_alert']] ?? 0),
                'max_stock_alert' => intval($data[$colMap['max_stock_alert']] ?? 0),
                'unit' => trim($data[$colMap['unit']] ?? '') ?: null,
                'base_unit_code' => trim($data[$colMap['base_unit_code']] ?? '') ?: null,
                'unit_conversion' => floatval($data[$colMap['unit_conversion']] ?? 1),
                'related_product_codes' => trim($data[$colMap['related_product_codes']] ?? '') ?: null,
                'image' => $imageList[0] ?? null,
                'images' => count($imageList) > 1 ? json_encode(array_slice($imageList, 1)) : null,
                'image_count' => count($imageList),
                'weight' => floatval($data[$colMap['weight']] ?? 0) ?: null,
                'is_active' => ($data[$colMap['is_active']] ?? 1) == 1,
                'is_available_online' => ($data[$colMap['is_available_online']] ?? 0) == 1,
                'description' => trim($data[$colMap['description']] ?? '') ?: null,
                'warehouse_location' => trim($data[$colMap['warehouse_location']] ?? '') ?: null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $this->db->table('products')->insert($product);
                $productId = $this->db->insertID();

                // Link category
                $catPath = $data[$colMap['category']] ?? '';
                if ($catPath && isset($categoryIds[$catPath])) {
                    $this->db->table('product_category_links')->insert([
                        'product_id' => $productId,
                        'category_id' => $categoryIds[$catPath],
                    ]);
                }

                $inserted++;
                if ($inserted % 100 == 0) {
                    CLI::write("  Processed {$inserted} products...", 'white');
                }
            } catch (\Exception $e) {
                $errors++;
                CLI::error("Error inserting {$code}: " . $e->getMessage());
            }
        }

        CLI::write("Products: {$inserted} inserted, {$errors} errors", 'green');
    }

    private function importSuppliers()
    {
        CLI::write("=== Importing Suppliers ===", 'yellow');
        $file = $this->findFile('DanhSachNhaCungCap');
        if (!$file) {
            CLI::error("Suppliers file not found");
            return;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $headers = $sheet->rangeToArray('A1:Z1', null, true, false)[0];

        CLI::write("Found {$highestRow} rows", 'white');

        $colMap = $this->findColumnIndexes($headers, [
            'Mã nhà cung cấp' => 'code',
            'Tên nhà cung cấp' => 'name',
            'Điện thoại' => 'phone',
            'Địa chỉ' => 'address',
            'Khu vực' => 'region',
            'Email' => 'email',
            'Công ty' => 'company',
            'Mã số thuế' => 'tax_code',
            'Nhóm nhà cung cấp' => 'group',
            'Nợ cần trả hiện tại' => 'debt',
        ]);

        $inserted = 0;
        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:Z{$row}", null, true, false)[0];
            
            $code = trim($data[$colMap['code']] ?? '');
            if (empty($code)) continue;

            $name = trim($data[$colMap['name']] ?? '');
            $supplier = [
                'code' => $code,
                'name' => $name,
                'name_vi' => $name,
                'phone' => trim($data[$colMap['phone']] ?? '') ?: null,
                'address' => trim($data[$colMap['address']] ?? '') ?: null,
                'email' => trim($data[$colMap['email']] ?? '') ?: null,
                'tax_code' => trim($data[$colMap['tax_code']] ?? '') ?: null,
                'region' => trim($data[$colMap['region']] ?? '') ?: null,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $this->db->table('suppliers')->insert($supplier);
                $inserted++;
            } catch (\Exception $e) {
                CLI::error("Error: " . $e->getMessage());
            }
        }

        CLI::write("Suppliers: {$inserted} inserted", 'green');
    }

    private function importCustomers()
    {
        CLI::write("=== Importing Customers ===", 'yellow');
        $file = $this->findFile('DanhSachKhachHang');
        if (!$file) {
            CLI::error("Customers file not found");
            return;
        }

        // Use streaming XML reader for large file
        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) {
            CLI::error("Cannot open xlsx file");
            return;
        }

        // Find the worksheet file
        $sheetFile = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (strpos($name, 'xl/worksheets/sheet') !== false) {
                $sheetFile = $name;
                break;
            }
        }

        if (!$sheetFile) {
            CLI::error("Cannot find worksheet");
            return;
        }

        $xml = $zip->getFromName($sheetFile);
        $zip->close();

        // Column mapping: A=0, B=1, etc
        // Headers from XML: A=Loại khách, B=Chi nhánh, C=Mã KH, D=Tên KH, E=ĐT, F=Địa chỉ, G=Khu vực, 
        // H=Phường, I=Công ty, J=MST, K=CMND, L=Ngày sinh, M=Giới tính, N=Email, O=FB, P=Nhóm KH,
        // Q=Ghi chú, R=Người tạo, S=Ngày tạo, T=Ngày GD cuối, U=Nợ, V=Tổng bán, W=Tổng bán net, X=Trạng thái
        $colMap = [
            'type' => 0,        // A - Loại khách
            'code' => 2,        // C - Mã khách hàng  
            'name' => 3,        // D - Tên khách hàng
            'phone' => 4,       // E - Điện thoại
            'address' => 5,     // F - Địa chỉ
            'region' => 6,      // G - Khu vực
            'ward' => 7,        // H - Phường/Xã
            'company' => 8,     // I - Công ty
            'tax_code' => 9,    // J - Mã số thuế
            'cccd' => 10,       // K - CMND/CCCD
            'birthday' => 11,   // L - Ngày sinh
            'gender' => 12,     // M - Giới tính
            'email' => 13,      // N - Email
            'facebook' => 14,   // O - Facebook
            'group' => 15,      // P - Nhóm KH
            'note' => 16,       // Q - Ghi chú
            'debt' => 20,       // U - Nợ cần thu
            'total_sales' => 21, // V - Tổng bán
            'status' => 23,     // X - Trạng thái
        ];

        // Parse XML using regex for speed
        preg_match_all('/<row r="(\d+)"[^>]*>(.*?)<\/row>/s', $xml, $matches, PREG_SET_ORDER);
        
        CLI::write("Found " . count($matches) . " rows", 'white');

        $inserted = 0;
        $errors = 0;
        
        foreach ($matches as $match) {
            $rowNum = (int) $match[1];
            if ($rowNum === 1) continue; // Skip header

            $rowXml = $match[2];
            $cells = [];
            
            // Extract cell values
            preg_match_all('/<c r="([A-Z]+)\d+"[^>]*>(?:<v>([^<]*)<\/v>)?/s', $rowXml, $cellMatches, PREG_SET_ORDER);
            foreach ($cellMatches as $cell) {
                $col = $this->colToIndex($cell[1]);
                $cells[$col] = $cell[2] ?? '';
            }

            $code = trim($cells[$colMap['code']] ?? '');
            if (empty($code)) continue;

            $gender = trim($cells[$colMap['gender']] ?? '');
            $genderMap = ['Nam' => 'MALE', 'Nữ' => 'FEMALE'];
            
            $customerType = trim($cells[$colMap['type']] ?? '');
            $typeMap = ['Cá nhân' => 'INDIVIDUAL', 'Công ty' => 'COMPANY'];

            $customer = [
                'code' => $code,
                'name' => trim($cells[$colMap['name']] ?? '') ?: $code,
                'phone' => trim($cells[$colMap['phone']] ?? '') ?: null,
                'address' => trim($cells[$colMap['address']] ?? '') ?: null,
                'province' => trim($cells[$colMap['region']] ?? '') ?: null,
                'ward' => trim($cells[$colMap['ward']] ?? '') ?: null,
                'email' => trim($cells[$colMap['email']] ?? '') ?: null,
                'gender' => $genderMap[$gender] ?? null,
                'customer_type' => $typeMap[$customerType] ?? 'INDIVIDUAL',
                'company_name' => trim($cells[$colMap['company']] ?? '') ?: null,
                'birthday' => $this->parseExcelDate($cells[$colMap['birthday']] ?? ''),
                'current_debt' => floatval($cells[$colMap['debt']] ?? 0),
                'total_sales' => floatval($cells[$colMap['total_sales']] ?? 0),
                'facebook' => trim($cells[$colMap['facebook']] ?? '') ?: null,
                'notes' => trim($cells[$colMap['note']] ?? '') ?: null,
                'tax_code' => trim($cells[$colMap['tax_code']] ?? '') ?: null,
                'cccd_cmnd' => trim($cells[$colMap['cccd']] ?? '') ?: null,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $this->db->table('customers')->insert($customer);
                $inserted++;
                if ($inserted % 1000 == 0) {
                    CLI::write("  Processed {$inserted} customers...", 'white');
                }
            } catch (\Exception $e) {
                $errors++;
                if ($errors <= 5) {
                    CLI::error("Error {$code}: " . $e->getMessage());
                }
            }
        }

        CLI::write("Customers: {$inserted} inserted, {$errors} errors", 'green');
    }
    
    private function colToIndex($col)
    {
        $index = 0;
        $len = strlen($col);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($col[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }
    
    private function parseExcelDate($value)
    {
        if (empty($value) || !is_numeric($value)) return null;
        
        // Excel date serial number to PHP date
        $days = (int) $value;
        if ($days < 1) return null;
        
        // Excel epoch is 1900-01-01, but has a bug treating 1900 as leap year
        $unixDays = $days - 25569; // Days between 1900-01-01 and 1970-01-01
        $timestamp = $unixDays * 86400;
        
        if ($timestamp < 0 || $timestamp > 4102444800) return null; // Valid range
        
        return date('Y-m-d', $timestamp);
    }

    private function importDeliveryPartners()
    {
        CLI::write("=== Importing Delivery Partners ===", 'yellow');
        $file = $this->findFile('DanhSachDoiTacGiaoHang');
        if (!$file) {
            CLI::error("Delivery partners file not found");
            return;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $headers = $sheet->rangeToArray('A1:Z1', null, true, false)[0];

        CLI::write("Found {$highestRow} rows, Headers: " . implode(', ', array_filter($headers)), 'white');

        $colMap = $this->findColumnIndexes($headers, [
            'Mã đối tác' => 'code',
            'Tên đối tác' => 'name',
            'Điện thoại' => 'phone',
            'Địa chỉ' => 'address',
            'Email' => 'email',
        ]);

        $inserted = 0;
        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:Z{$row}", null, true, false)[0];
            
            $code = trim($data[$colMap['code']] ?? '');
            if (empty($code)) continue;

            $partner = [
                'code' => $code,
                'name' => trim($data[$colMap['name']] ?? '') ?: $code,
                'type' => 'distributor',
                'phone' => trim($data[$colMap['phone']] ?? '') ?: '0000000000',
                'address' => trim($data[$colMap['address']] ?? '') ?: null,
                'email' => trim($data[$colMap['email']] ?? '') ?: null,
                'status' => 'active',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $this->db->table('partners')->insert($partner);
                $inserted++;
            } catch (\Exception $e) {
                CLI::error("Error: " . $e->getMessage());
            }
        }

        CLI::write("Delivery Partners: {$inserted} inserted", 'green');
    }

    private function importPurchaseOrders()
    {
        CLI::write("=== Importing Purchase Orders ===", 'yellow');
        
        // Import header first
        $headerFile = $this->findFile('DanhSachNhapHang_');
        $detailFile = $this->findFile('DanhSachChiTietNhapHang');
        
        if (!$headerFile || !$detailFile) {
            CLI::error("Purchase order files not found");
            return;
        }

        // Read headers
        $spreadsheet = IOFactory::load($headerFile);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $headers = $sheet->rangeToArray('A1:AZ1', null, true, false)[0];

        CLI::write("Headers file has {$highestRow} rows", 'white');
        CLI::write("Headers: " . implode(', ', array_filter($headers)), 'white');

        $colMap = $this->findColumnIndexes($headers, [
            'Mã nhập hàng' => 'code',
            'Thời gian' => 'time',
            'Mã NCC' => 'supplier_code',
            'Tên NCC' => 'supplier_name',
            'Trạng thái' => 'status',
            'Tổng số lượng' => 'total_qty',
            'Tổng tiền hàng' => 'total_amount',
            'Giảm giá' => 'discount',
            'Cần trả NCC' => 'amount_due',
            'Đã trả NCC' => 'amount_paid',
            'Ghi chú' => 'note',
            'Chi nhánh' => 'branch',
        ]);

        // Get supplier map
        $supplierMap = [];
        $suppliers = $this->db->table('suppliers')->select('id, code')->get()->getResultArray();
        foreach ($suppliers as $s) {
            $supplierMap[$s['code']] = $s['id'];
        }

        // Ensure default warehouse
        $warehouseId = $this->ensureDefaultWarehouse();

        $poMap = []; // code => id
        $inserted = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:AZ{$row}", null, true, false)[0];
            
            $code = trim($data[$colMap['code']] ?? '');
            if (empty($code)) continue;

            $supplierCode = trim($data[$colMap['supplier_code']] ?? '');
            $supplierId = $supplierMap[$supplierCode] ?? null;

            $statusText = trim($data[$colMap['status']] ?? '');
            $status = $this->mapPOStatus($statusText);

            $subtotal = floatval(str_replace(',', '', $data[$colMap['total_amount']] ?? 0));
            $totalAmount = floatval(str_replace(',', '', $data[$colMap['amount_due']] ?? 0));
            $paidAmount = floatval(str_replace(',', '', $data[$colMap['amount_paid']] ?? 0));
            
            $paymentStatus = 'unpaid';
            if ($paidAmount >= $totalAmount && $totalAmount > 0) {
                $paymentStatus = 'paid';
            } elseif ($paidAmount > 0) {
                $paymentStatus = 'partial';
            }

            $po = [
                'code' => $code,
                'po_number' => $code,
                'supplier_id' => $supplierId,
                'warehouse_id' => $warehouseId,
                'order_date' => $this->parseDateTime($data[$colMap['time']] ?? ''),
                'status' => $status,
                'subtotal' => $subtotal,
                'total' => $totalAmount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'payment_status' => $paymentStatus,
                'notes' => trim($data[$colMap['note']] ?? '') ?: null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $this->db->table('purchase_orders')->insert($po);
                $poMap[$code] = $this->db->insertID();
                $inserted++;
            } catch (\Exception $e) {
                CLI::error("Error inserting PO {$code}: " . $e->getMessage());
            }
        }

        CLI::write("Purchase Orders: {$inserted} inserted", 'green');

        // Now import items
        $this->importPurchaseOrderItems($detailFile, $poMap);
    }

    private function importPurchaseOrderItems($file, $poMap)
    {
        CLI::write("=== Importing Purchase Order Items ===", 'yellow');
        
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $headers = $sheet->rangeToArray('A1:AZ1', null, true, false)[0];

        CLI::write("Detail file has {$highestRow} rows", 'white');
        CLI::write("Headers: " . implode(', ', array_filter($headers)), 'white');

        // Detail headers: Chi nhánh(0), Mã nhập hàng(1), ..., Mã hàng(19), Tên hàng(20), ..., Giá nhập(27), Thành tiền(28), Số lượng(29)
        $colMap = $this->findColumnIndexes($headers, [
            'Mã nhập hàng' => 'po_code',
            'Mã hàng' => 'product_code',
            'Tên hàng' => 'product_name',
            'Số lượng' => 'quantity',
            'Giá nhập' => 'unit_price',
            'Thành tiền' => 'total',
        ]);

        // Get product map
        $productMap = [];
        $products = $this->db->table('products')->select('id, code')->get()->getResultArray();
        foreach ($products as $p) {
            $productMap[$p['code']] = $p['id'];
        }

        $inserted = 0;
        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:AZ{$row}", null, true, false)[0];
            
            $poCode = trim($data[$colMap['po_code']] ?? '');
            $productCode = trim($data[$colMap['product_code']] ?? '');
            
            if (empty($poCode) || !isset($poMap[$poCode])) continue;
            
            $productId = $productMap[$productCode] ?? null;

            $qty = floatval($data[$colMap['quantity']] ?? 0);
            $unitPrice = floatval(str_replace(',', '', $data[$colMap['unit_price']] ?? 0));
            $total = floatval(str_replace(',', '', $data[$colMap['total']] ?? 0));

            $item = [
                'purchase_order_id' => $poMap[$poCode],
                'product_id' => $productId,
                'quantity' => $qty,
                'received_quantity' => $qty,
                'rate' => $unitPrice,
                'unit_price' => $unitPrice,
                'amount' => $total,
                'total_price' => $total,
                'created_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $this->db->table('purchase_order_items')->insert($item);
                $inserted++;
            } catch (\Exception $e) {
                // Skip duplicates
            }
        }

        CLI::write("Purchase Order Items: {$inserted} inserted", 'green');
    }

    private function importInvoices()
    {
        CLI::write("=== Importing Invoices ===", 'yellow');
        $file = $this->findFile('DanhSachHoaDon');
        if (!$file) {
            CLI::error("Invoice file not found");
            return;
        }

        // Use streaming XML reader for large file
        $zip = new \ZipArchive();
        if ($zip->open($file) !== true) {
            CLI::error("Cannot open xlsx file");
            return;
        }

        $sheetFile = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (strpos($name, 'xl/worksheets/sheet') !== false) {
                $sheetFile = $name;
                break;
            }
        }

        if (!$sheetFile) {
            CLI::error("Cannot find worksheet");
            return;
        }

        $xml = $zip->getFromName($sheetFile);
        $zip->close();

        // Column mapping from XML headers:
        // A(0)=Mã HĐ, E(4)=Thời gian, K(10)=Mã KH, L(11)=Khách hàng, S(18)=Chi nhánh,
        // V(21)=Kênh bán, X(23)=Ghi chú, Y(24)=Tổng tiền hàng, Z(25)=Giảm giá,
        // AD(29)=Khách cần trả, AE(30)=Khách đã trả, AK(36)=Trạng thái
        $colMap = [
            'code' => 0,        // A - Mã hóa đơn
            'time' => 4,        // E - Thời gian
            'customer_code' => 10, // K - Mã KH
            'customer_name' => 11, // L - Khách hàng
            'branch' => 18,     // S - Chi nhánh
            'channel' => 21,    // V - Kênh bán
            'note' => 23,       // X - Ghi chú
            'subtotal' => 24,   // Y - Tổng tiền hàng
            'discount' => 25,   // Z - Giảm giá
            'total' => 29,      // AD - Khách cần trả
            'paid' => 30,       // AE - Khách đã trả
            'status' => 36,     // AK - Trạng thái
        ];

        // Get customer map
        $customerMap = [];
        $customers = $this->db->table('customers')->select('id, code')->get()->getResultArray();
        foreach ($customers as $c) {
            $customerMap[$c['code']] = $c['id'];
        }

        preg_match_all('/<row r="(\d+)"[^>]*>(.*?)<\/row>/s', $xml, $matches, PREG_SET_ORDER);
        
        CLI::write("Found " . count($matches) . " rows", 'white');

        $inserted = 0;
        $errors = 0;
        
        foreach ($matches as $match) {
            $rowNum = (int) $match[1];
            if ($rowNum === 1) continue;

            $rowXml = $match[2];
            $cells = [];
            
            preg_match_all('/<c r="([A-Z]+)\d+"[^>]*>(?:<v>([^<]*)<\/v>)?/s', $rowXml, $cellMatches, PREG_SET_ORDER);
            foreach ($cellMatches as $cell) {
                $col = $this->colToIndex($cell[1]);
                $cells[$col] = $cell[2] ?? '';
            }

            $code = trim($cells[$colMap['code']] ?? '');
            if (empty($code)) continue;

            $customerCode = trim($cells[$colMap['customer_code']] ?? '');
            $customerId = $customerMap[$customerCode] ?? null;

            $statusText = trim($cells[$colMap['status']] ?? '');
            $status = $this->mapInvoiceStatus($statusText);

            $subtotal = floatval($cells[$colMap['subtotal']] ?? 0);
            $discount = floatval($cells[$colMap['discount']] ?? 0);
            $total = floatval($cells[$colMap['total']] ?? 0);
            $paid = floatval($cells[$colMap['paid']] ?? 0);

            $paymentStatus = 'unpaid';
            if ($paid >= $total && $total > 0) {
                $paymentStatus = 'paid';
            } elseif ($paid > 0) {
                $paymentStatus = 'partial';
            }

            $invoice = [
                'invoice_number' => $code,
                'customer_id' => $customerId,
                'issue_date' => $this->parseExcelDate($cells[$colMap['time']] ?? ''),
                'invoice_status' => $status,
                'sales_channel' => trim($cells[$colMap['channel']] ?? '') ?: null,
                'subtotal' => $subtotal,
                'goods_total' => $subtotal,
                'discount_total' => $discount,
                'net_total' => $total,
                'customer_payable' => $total,
                'customer_paid' => $paid,
                'total_paid' => $paid,
                'total' => $total,
                'payment_status' => $paymentStatus,
                'notes' => trim($cells[$colMap['note']] ?? '') ?: null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $this->db->table('invoices')->insert($invoice);
                $inserted++;
                if ($inserted % 5000 == 0) {
                    CLI::write("  Processed {$inserted} invoices...", 'white');
                }
            } catch (\Exception $e) {
                $errors++;
                if ($errors <= 5) {
                    CLI::error("Error {$code}: " . $e->getMessage());
                }
            }
        }

        CLI::write("Invoices: {$inserted} inserted, {$errors} errors", 'green');
    }

    private function importStockTransfers()
    {
        CLI::write("=== Importing Stock Transfers ===", 'yellow');
        $file = $this->findFile('DanhSachChuyenHang_');
        if (!$file) {
            CLI::error("Stock transfers file not found");
            return;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $headers = $sheet->rangeToArray('A1:Z1', null, true, false)[0];

        CLI::write("Found {$highestRow} rows", 'white');

        $colMap = $this->findColumnIndexes($headers, [
            'Mã chuyển hàng' => 'code',
            'Ngày chuyển' => 'transfer_date',
            'Ngày nhận' => 'receive_date',
            'Tổng SL chuyển' => 'qty_sent',
            'Giá trị chuyển' => 'value_sent',
            'Tổng SL nhận' => 'qty_received',
            'Giá trị nhận' => 'value_received',
            'Tổng số mặt hàng' => 'total_items',
            'Ghi chú' => 'note',
            'Trạng thái' => 'status',
        ]);

        // Ensure branches exist
        $defaultBranchId = $this->ensureDefaultBranch();

        $inserted = 0;
        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:Z{$row}", null, true, false)[0];
            
            $code = trim($data[$colMap['code']] ?? '');
            if (empty($code)) continue;

            $statusText = trim($data[$colMap['status']] ?? '');
            $statusMap = ['Đã nhận' => 'received', 'Đang chuyển' => 'in_transit', 'Đã hủy' => 'cancelled'];
            $status = $statusMap[$statusText] ?? 'received';

            $transfer = [
                'code' => $code,
                'from_branch_id' => $defaultBranchId,
                'to_branch_id' => $defaultBranchId,
                'transfer_date' => $this->parseDateTime($data[$colMap['transfer_date']] ?? ''),
                'receive_date' => $this->parseDateTime($data[$colMap['receive_date']] ?? '') ?: null,
                'status' => $status,
                'total_items' => intval($data[$colMap['total_items']] ?? 0),
                'quantity_sent' => floatval($data[$colMap['qty_sent']] ?? 0),
                'value_sent' => floatval(str_replace(',', '', $data[$colMap['value_sent']] ?? 0)),
                'quantity_received' => floatval($data[$colMap['qty_received']] ?? 0),
                'value_received' => floatval(str_replace(',', '', $data[$colMap['value_received']] ?? 0)),
                'notes' => trim($data[$colMap['note']] ?? '') ?: null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $this->db->table('stock_transfers')->insert($transfer);
                $inserted++;
            } catch (\Exception $e) {
                CLI::error("Error {$code}: " . $e->getMessage());
            }
        }

        CLI::write("Stock Transfers: {$inserted} inserted", 'green');
    }

    private function importStockAudits()
    {
        CLI::write("=== Importing Stock Audits ===", 'yellow');
        $file = $this->findFile('DanhSachKiemKho');
        if (!$file) {
            CLI::error("Stock audits file not found");
            return;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $headers = $sheet->rangeToArray('A1:Z1', null, true, false)[0];

        CLI::write("Found {$highestRow} rows", 'white');

        $colMap = $this->findColumnIndexes($headers, [
            'Mã kiểm kho' => 'code',
            'Thời gian' => 'time',
            'Ghi chú' => 'note',
            'Trạng thái' => 'status',
        ]);

        $defaultBranchId = $this->ensureDefaultBranch();

        $inserted = 0;
        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:Z{$row}", null, true, false)[0];
            
            $code = trim($data[$colMap['code']] ?? '');
            if (empty($code)) continue;

            $statusText = trim($data[$colMap['status']] ?? '');
            $statusMap = ['Đã cân bằng' => 'completed', 'Phiếu tạm' => 'draft'];
            $status = $statusMap[$statusText] ?? 'completed';

            $audit = [
                'recon_number' => $code,
                'branch_id' => $defaultBranchId,
                'status' => $status,
                'notes' => trim($data[$colMap['note']] ?? '') ?: null,
                'created_at' => $this->parseDateTime($data[$colMap['time']] ?? ''),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $this->db->table('stock_reconciliations')->insert($audit);
                $inserted++;
            } catch (\Exception $e) {
                CLI::error("Error {$code}: " . $e->getMessage());
            }
        }

        CLI::write("Stock Audits: {$inserted} inserted", 'green');
    }

    private function importStockDisposals()
    {
        CLI::write("=== Importing Stock Disposals ===", 'yellow');
        $file = $this->findFile('DanhSachXuatHuy');
        if (!$file) {
            CLI::error("Stock disposals file not found");
            return;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $headers = $sheet->rangeToArray('A1:Z1', null, true, false)[0];

        CLI::write("Found {$highestRow} rows", 'white');

        $colMap = $this->findColumnIndexes($headers, [
            'Mã xuất hủy' => 'code',
            'Thời gian' => 'time',
            'Tổng giá trị hủy' => 'total_value',
            'Ghi chú' => 'note',
            'Trạng thái' => 'status',
        ]);

        $defaultBranchId = $this->ensureDefaultBranch();

        $inserted = 0;
        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:Z{$row}", null, true, false)[0];
            
            $code = trim($data[$colMap['code']] ?? '');
            if (empty($code)) continue;

            $statusText = trim($data[$colMap['status']] ?? '');
            $statusMap = ['Đã xuất hủy' => 'completed', 'Phiếu tạm' => 'draft', 'Đã hủy' => 'cancelled'];
            $status = $statusMap[$statusText] ?? 'completed';

            $disposal = [
                'code' => $code,
                'branch_id' => $defaultBranchId,
                'disposed_at' => $this->parseDateTime($data[$colMap['time']] ?? ''),
                'total_value' => floatval(str_replace(',', '', $data[$colMap['total_value']] ?? 0)),
                'status' => $status,
                'notes' => trim($data[$colMap['note']] ?? '') ?: null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $this->db->table('stock_disposals')->insert($disposal);
                $inserted++;
            } catch (\Exception $e) {
                CLI::error("Error {$code}: " . $e->getMessage());
            }
        }

        CLI::write("Stock Disposals: {$inserted} inserted", 'green');
    }

    private function importReturns()
    {
        CLI::write("=== Importing Returns ===", 'yellow');
        $file = $this->findFile('DanhSachTraHang_');
        if (!$file) {
            CLI::error("Returns file not found");
            return;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $headers = $sheet->rangeToArray('A1:Z1', null, true, false)[0];

        CLI::write("Found {$highestRow} rows", 'white');
        CLI::write("Headers: " . implode(', ', array_filter($headers)), 'white');

        $colMap = $this->findColumnIndexes($headers, [
            'Mã trả hàng' => 'code',
            'Mã hóa đơn' => 'invoice_code',
            'Thời gian' => 'time',
            'Khách hàng' => 'customer_name',
            'Tổng tiền hàng' => 'subtotal',
            'Giảm giá' => 'discount',
            'Cần trả khách' => 'refund_amount',
            'Đã trả khách' => 'refunded',
            'Ghi chú' => 'note',
            'Trạng thái' => 'status',
        ]);

        // Get customer map by name for linking
        $customerMap = [];
        $customers = $this->db->table('customers')->select('id, name')->get()->getResultArray();
        foreach ($customers as $c) {
            $customerMap[$c['name']] = $c['id'];
        }

        // Get invoice map
        $invoiceMap = [];
        $invoices = $this->db->table('invoices')->select('id, invoice_number')->get()->getResultArray();
        foreach ($invoices as $inv) {
            $invoiceMap[$inv['invoice_number']] = $inv['id'];
        }

        $defaultBranchId = $this->ensureDefaultBranch();

        $inserted = 0;
        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:Z{$row}", null, true, false)[0];
            
            $code = trim($data[$colMap['code']] ?? '');
            if (empty($code)) continue;

            $customerName = trim($data[$colMap['customer_name']] ?? '');
            $customerId = $customerMap[$customerName] ?? null;
            
            $invoiceCode = trim($data[$colMap['invoice_code']] ?? '');
            $invoiceId = $invoiceMap[$invoiceCode] ?? null;

            $statusText = trim($data[$colMap['status']] ?? '');
            $statusMap = ['Hoàn thành' => 'completed', 'Phiếu tạm' => 'draft', 'Đã hủy' => 'cancelled'];
            $status = $statusMap[$statusText] ?? 'completed';

            $returnAmount = floatval(str_replace(',', '', $data[$colMap['subtotal']] ?? 0));
            $refundAmount = floatval(str_replace(',', '', $data[$colMap['refund_amount']] ?? 0));

            $return = [
                'return_number' => $code,
                'customer_id' => $customerId,
                'return_amount' => $returnAmount,
                'refund_amount' => $refundAmount,
                'status' => $status,
                'notes' => trim($data[$colMap['note']] ?? '') ?: null,
                'created_at' => $this->parseDateTime($data[$colMap['time']] ?? ''),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            try {
                $this->db->table('returns')->insert($return);
                $inserted++;
            } catch (\Exception $e) {
                CLI::error("Error {$code}: " . $e->getMessage());
            }
        }

        CLI::write("Returns: {$inserted} inserted", 'green');
    }

    private function ensureDefaultBranch()
    {
        $branch = $this->db->table('branches')->where('code', 'CN-CHINH')->get()->getRowArray();
        if ($branch) return $branch['id'];

        $this->db->table('branches')->insert([
            'code' => 'CN-CHINH',
            'name' => 'Chi nhánh chính',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insertID();
    }

    private function importAttributes()
    {
        CLI::write("=== Importing Attributes from Products ===", 'yellow');
        $file = $this->findFile('DanhSachSanPham');
        if (!$file) {
            CLI::error("Products file not found");
            return;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $headers = $sheet->rangeToArray('A1:AA1', null, true, false)[0];

        // Find attribute column (Thuộc tính)
        $attrCol = -1;
        $codeCol = -1;
        foreach ($headers as $i => $h) {
            if ($h && strpos($h, 'Thuộc tính') !== false) $attrCol = $i;
            if ($h && strpos($h, 'Mã hàng') !== false) $codeCol = $i;
        }

        if ($attrCol < 0) {
            CLI::error("Attribute column not found");
            return;
        }

        CLI::write("Found attribute column at index {$attrCol}", 'white');

        // Collect all unique attributes and options
        $attributeOptions = []; // ['MÀU SẮC' => ['NS', 'D', 'Navy', ...]]
        $productAttributes = []; // ['VDP03' => [['attr' => 'MÀU SẮC', 'opt' => 'NS'], ...]]

        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:AA{$row}", null, true, false)[0];
            $code = trim($data[$codeCol] ?? '');
            $attrStr = trim($data[$attrCol] ?? '');
            
            if (empty($attrStr) || empty($code)) continue;

            // Parse "MÀU SẮC:NS" or "MÀU SẮC:NS;KÍCH THƯỚC:L"
            $pairs = explode(';', $attrStr);
            foreach ($pairs as $pair) {
                $pair = trim($pair);
                if (strpos($pair, ':') !== false) {
                    [$attrName, $optName] = explode(':', $pair, 2);
                    $attrName = trim($attrName);
                    $optName = trim($optName);
                    
                    if ($attrName && $optName) {
                        $attributeOptions[$attrName][$optName] = true;
                        $productAttributes[$code][] = ['attr' => $attrName, 'opt' => $optName];
                    }
                }
            }
        }

        CLI::write("Found " . count($attributeOptions) . " unique attributes", 'white');

        // Insert attributes into product_attributes (not attributes table - FK constraint)
        $attrIdMap = []; // ['MÀU SẮC' => id]
        foreach ($attributeOptions as $attrName => $options) {
            $existing = $this->db->table('product_attributes')->where('name', $attrName)->get()->getRowArray();
            if ($existing) {
                $attrIdMap[$attrName] = $existing['id'];
            } else {
                $this->db->table('product_attributes')->insert([
                    'name' => $attrName,
                    'slug' => $this->createSlug($attrName),
                    'type' => 'select',
                    'status' => 'active',
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $attrIdMap[$attrName] = $this->db->insertID();
            }
        }
        CLI::write("Attributes: " . count($attrIdMap) . " created/found", 'green');

        // Insert attribute options into product_attribute_options (not attribute_options - FK constraint)
        $optIdMap = []; // ['MÀU SẮC:NS' => id]
        foreach ($attributeOptions as $attrName => $options) {
            $attrId = $attrIdMap[$attrName];
            foreach (array_keys($options) as $optName) {
                $key = "{$attrName}:{$optName}";
                $existing = $this->db->table('product_attribute_options')
                    ->where('attribute_id', $attrId)
                    ->where('option_name', $optName)
                    ->get()->getRowArray();
                    
                if ($existing) {
                    $optIdMap[$key] = $existing['id'];
                } else {
                    $this->db->table('product_attribute_options')->insert([
                        'attribute_id' => $attrId,
                        'option_name' => $optName,
                        'status' => 'active',
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $optIdMap[$key] = $this->db->insertID();
                }
            }
        }
        CLI::write("Attribute Options: " . count($optIdMap) . " created/found", 'green');

        // Get product ID map
        $productMap = [];
        $products = $this->db->table('products')->select('id, code')->get()->getResultArray();
        foreach ($products as $p) {
            $productMap[$p['code']] = $p['id'];
        }

        // Link products with attributes
        $linked = 0;
        foreach ($productAttributes as $code => $attrs) {
            $productId = $productMap[$code] ?? null;
            if (!$productId) continue;

            foreach ($attrs as $attr) {
                $attrId = $attrIdMap[$attr['attr']] ?? null;
                $optId = $optIdMap["{$attr['attr']}:{$attr['opt']}"] ?? null;
                
                if (!$attrId || !$optId) continue;

                // Check if already exists
                $exists = $this->db->table('product_attribute_values')
                    ->where('product_id', $productId)
                    ->where('attribute_id', $attrId)
                    ->where('option_id', $optId)
                    ->countAllResults() > 0;

                if (!$exists) {
                    $this->db->table('product_attribute_values')->insert([
                        'product_id' => $productId,
                        'attribute_id' => $attrId,
                        'option_id' => $optId,
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $linked++;
                }
            }
        }

        CLI::write("Product-Attribute Links: {$linked} created", 'green');
    }

    private function importPriceLists()
    {
        CLI::write("=== Importing Price Lists ===", 'yellow');
        $file = $this->findFile('BangGia');
        if (!$file) {
            CLI::error("BangGia file not found");
            return;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $headers = $sheet->rangeToArray('A1:Z1', null, true, false)[0];

        // Find columns
        $cols = [];
        foreach ($headers as $i => $h) {
            if (!$h) continue;
            if (strpos($h, 'Mã hàng') !== false) $cols['code'] = $i;
            if (strpos($h, 'Giá vốn') !== false) $cols['cost'] = $i;
            if (strpos($h, 'Bảng giá chung') !== false) $cols['price'] = $i;
        }

        CLI::write("Found columns: code={$cols['code']}, cost={$cols['cost']}, price={$cols['price']}", 'white');

        // Create default price list
        $priceList = $this->db->table('price_lists')->where('name', 'Bảng giá chung')->get()->getRowArray();
        if (!$priceList) {
            $this->db->table('price_lists')->insert([
                'name' => 'Bảng giá chung',
                'type' => 'default',
                'is_active' => 1,
                'is_system' => 1,
                'priority' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $priceListId = $this->db->insertID();
            CLI::write("Created price list: Bảng giá chung (ID: {$priceListId})", 'green');
        } else {
            $priceListId = $priceList['id'];
            CLI::write("Using existing price list: Bảng giá chung (ID: {$priceListId})", 'white');
        }

        // Get product map (code => id)
        $productMap = [];
        $products = $this->db->table('products')->select('id, code')->where('deleted_at', null)->get()->getResultArray();
        foreach ($products as $p) {
            $productMap[$p['code']] = $p['id'];
        }

        // Get variant map (sku => [id, product_id])
        $variantMap = [];
        $variants = $this->db->table('product_variants_v2')->select('id, product_id, sku')->where('deleted_at', null)->get()->getResultArray();
        foreach ($variants as $v) {
            $variantMap[$v['sku']] = ['id' => $v['id'], 'product_id' => $v['product_id']];
        }

        $imported = 0;
        $updated = 0;

        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:Z{$row}", null, true, false)[0];
            $code = trim($data[$cols['code']] ?? '');
            $price = (float) ($data[$cols['price']] ?? 0);
            $cost = (float) ($data[$cols['cost']] ?? 0);

            if (empty($code) || $price <= 0) continue;

            // Check if it's a variant or product
            $productId = null;
            $variantId = null;

            if (isset($variantMap[$code])) {
                // It's a variant
                $variantId = $variantMap[$code]['id'];
                $productId = $variantMap[$code]['product_id'];
            } elseif (isset($productMap[$code])) {
                // It's a product
                $productId = $productMap[$code];
            } else {
                continue; // Code not found
            }

            // Check existing
            $existing = $this->db->table('price_list_items')
                ->where('price_list_id', $priceListId)
                ->where('product_id', $productId)
                ->where('variant_id', $variantId)
                ->get()->getRowArray();

            if ($existing) {
                $this->db->table('price_list_items')
                    ->where('id', $existing['id'])
                    ->update(['price' => $price, 'updated_at' => date('Y-m-d H:i:s')]);
                $updated++;
            } else {
                $this->db->table('price_list_items')->insert([
                    'price_list_id' => $priceListId,
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'price' => $price,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $imported++;
            }

            // Also update cost price on product/variant
            if ($cost > 0) {
                if ($variantId) {
                    $this->db->table('product_variants_v2')->where('id', $variantId)->update(['cost_price' => $cost]);
                } else {
                    $this->db->table('products')->where('id', $productId)->update(['purchase_price' => $cost]);
                }
            }
        }

        CLI::write("Price List Items: {$imported} created, {$updated} updated", 'green');
    }

    private function importCashBook()
    {
        CLI::write("=== Importing Cash Book (Sổ quỹ) ===", 'yellow');
        $file = $this->findFile('SoQuy');
        if (!$file) {
            CLI::error("SoQuy file not found");
            return;
        }

        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $headers = $sheet->rangeToArray('A1:O1', null, true, false)[0];

        CLI::write("Found {$highestRow} rows", 'white');

        $imported = 0;
        $branchId = $this->ensureDefaultBranch();

        // Find column indexes
        $cols = [];
        foreach ($headers as $i => $h) {
            if (!$h) continue;
            if (strpos($h, 'Mã phiếu') !== false) $cols['code'] = $i;
            if (strpos($h, 'Loại thu chi') !== false) $cols['type'] = $i;
            if (strpos($h, 'Giá trị') !== false) $cols['amount'] = $i;
            if (strpos($h, 'Thời gian') !== false && !isset($cols['date'])) $cols['date'] = $i;
            if (strpos($h, 'Người tạo') !== false) $cols['creator'] = $i;
            if (strpos($h, 'Nhân viên') !== false) $cols['staff'] = $i;
            if (strpos($h, 'Mã người nộp') !== false) $cols['payer_code'] = $i;
            if (strpos($h, 'Người nộp') !== false) $cols['payer_name'] = $i;
            if (strpos($h, 'Số điện thoại') !== false) $cols['phone'] = $i;
            if (strpos($h, 'Địa chỉ') !== false) $cols['address'] = $i;
            if (strpos($h, 'Nội dung chuyển khoản') !== false) $cols['transfer'] = $i;
            if (strpos($h, 'Ghi chú') !== false) $cols['note'] = $i;
        }

        for ($row = 2; $row <= $highestRow; $row++) {
            $data = $sheet->rangeToArray("A{$row}:O{$row}", null, true, false)[0];
            
            $code = trim($data[$cols['code']] ?? '');
            if (empty($code)) continue;

            // Determine type (Thu = RECEIPT, Chi = PAYMENT)
            $loaiThuChi = trim($data[$cols['type']] ?? '');
            $type = (stripos($loaiThuChi, 'Thu') !== false) ? 'RECEIPT' : 'PAYMENT';
            
            // Parse amount
            $amount = abs((float) str_replace([',', ' '], '', $data[$cols['amount']] ?? 0));
            if ($amount <= 0) continue;

            // Parse date
            $transDate = $this->parseExcelDate($data[$cols['date']] ?? '') ?: date('Y-m-d');

            // Check if exists
            $exists = $this->db->table('cash_transactions')
                ->where('reference_code', $code)
                ->countAllResults() > 0;
            
            if ($exists) continue;

            $this->db->table('cash_transactions')->insert([
                'type' => $type,
                'amount' => $amount,
                'category' => $loaiThuChi ?: ($type === 'RECEIPT' ? 'Thu khác' : 'Chi khác'),
                'status' => 'completed',
                'reference_code' => $code,
                'branch_id' => $branchId,
                'created_by' => 1,
                'created_by_name' => trim($data[$cols['creator'] ?? 99] ?? 'System'),
                'staff_name' => trim($data[$cols['staff'] ?? 99] ?? ''),
                'payer_code' => trim($data[$cols['payer_code'] ?? 99] ?? ''),
                'payer_name' => trim($data[$cols['payer_name'] ?? 99] ?? ''),
                'payer_phone' => trim($data[$cols['phone'] ?? 99] ?? ''),
                'payer_address' => trim($data[$cols['address'] ?? 99] ?? ''),
                'transfer_note' => trim($data[$cols['transfer'] ?? 99] ?? ''),
                'note' => trim($data[$cols['note'] ?? 99] ?? ''),
                'transaction_date' => $transDate,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            $imported++;

            if ($imported % 2000 === 0) {
                CLI::write("Imported {$imported} transactions...", 'white');
            }
        }

        CLI::write("Cash Transactions: {$imported} imported", 'green');
    }

    // Helper methods
    private function findFile($prefix)
    {
        $files = glob($this->basePath . $prefix . '*.xlsx');
        return $files[0] ?? null;
    }

    private function findColumnIndexes($headers, $mapping)
    {
        $result = [];
        foreach ($mapping as $kvHeader => $field) {
            foreach ($headers as $i => $h) {
                if ($h !== null && strpos($h, $kvHeader) !== false) {
                    $result[$field] = $i;
                    break;
                }
            }
            if (!isset($result[$field])) {
                $result[$field] = -1;
            }
        }
        return $result;
    }

    private function buildCategoryTree(&$categories, $parts, $parent = null)
    {
        if (empty($parts)) return;
        
        $name = array_shift($parts);
        $key = $parent ? "{$parent}>>{$name}" : $name;
        
        if (!isset($categories[$key])) {
            $categories[$key] = [
                'name' => $name,
                'parent_key' => $parent,
                'children' => []
            ];
        }
        
        if (!empty($parts)) {
            $this->buildCategoryTree($categories[$key]['children'], $parts, $key);
            $this->buildCategoryTree($categories, $parts, $key);
        }
    }

    private function insertCategories($categories)
    {
        $idMap = [];
        
        // First pass - insert root categories
        foreach ($categories as $key => $cat) {
            if ($cat['parent_key'] === null) {
                $this->db->table('product_categories')->insert([
                    'name' => $cat['name'],
                    'slug' => $this->createSlug($cat['name']),
                    'parent_id' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
                $idMap[$key] = $this->db->insertID();
            }
        }

        // Second pass - insert children (with retry for deep nesting)
        $remaining = array_filter($categories, fn($c) => $c['parent_key'] !== null);
        $maxIterations = 10;
        
        while (!empty($remaining) && $maxIterations-- > 0) {
            foreach ($remaining as $key => $cat) {
                if (isset($idMap[$cat['parent_key']])) {
                    $this->db->table('product_categories')->insert([
                        'name' => $cat['name'],
                        'slug' => $this->createSlug($cat['name']),
                        'parent_id' => $idMap[$cat['parent_key']],
                        'created_at' => date('Y-m-d H:i:s'),
                    ]);
                    $idMap[$key] = $this->db->insertID();
                    unset($remaining[$key]);
                }
            }
        }

        return $idMap;
    }

    private function createSlug($text)
    {
        $slug = $this->removeVietnameseAccents($text);
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        $slug = trim($slug, '-');
        return $slug ?: 'item-' . time();
    }

    private function removeVietnameseAccents($str)
    {
        $str = preg_replace("/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/", 'a', $str);
        $str = preg_replace("/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/", 'e', $str);
        $str = preg_replace("/(ì|í|ị|ỉ|ĩ)/", 'i', $str);
        $str = preg_replace("/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/", 'o', $str);
        $str = preg_replace("/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/", 'u', $str);
        $str = preg_replace("/(ỳ|ý|ỵ|ỷ|ỹ)/", 'y', $str);
        $str = preg_replace("/(đ)/", 'd', $str);
        $str = preg_replace("/(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)/", 'A', $str);
        $str = preg_replace("/(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)/", 'E', $str);
        $str = preg_replace("/(Ì|Í|Ị|Ỉ|Ĩ)/", 'I', $str);
        $str = preg_replace("/(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)/", 'O', $str);
        $str = preg_replace("/(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)/", 'U', $str);
        $str = preg_replace("/(Ỳ|Ý|Ỵ|Ỷ|Ỹ)/", 'Y', $str);
        $str = preg_replace("/(Đ)/", 'D', $str);
        return $str;
    }

    private function mapProductType($kvType)
    {
        $map = [
            'Hàng hóa' => 'goods',
            'Dịch vụ' => 'service',
            'Combo' => 'combo',
            'Sản xuất' => 'manufactured',
        ];
        return $map[$kvType] ?? 'goods';
    }

    private function mapPOStatus($status)
    {
        $map = [
            'Phiếu tạm' => 'draft',
            'Đã nhập hàng' => 'completed',
            'Đã hủy' => 'cancelled',
        ];
        return $map[$status] ?? 'completed';
    }

    private function mapInvoiceStatus($status)
    {
        $map = [
            'Hoàn thành' => 'completed',
            'Đã hủy' => 'cancelled',
            'Phiếu tạm' => 'draft',
        ];
        return $map[$status] ?? 'completed';
    }

    private function parseDate($dateStr)
    {
        if (empty($dateStr)) return null;
        
        // Try DD/MM/YYYY format
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dateStr, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        
        return null;
    }

    private function parseDateTime($dtStr)
    {
        if (empty($dtStr)) return date('Y-m-d H:i:s');
        
        // Try DD/MM/YYYY HH:MM format
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})\s+(\d{1,2}):(\d{2})$/', $dtStr, $m)) {
            return sprintf('%s-%02d-%02d %02d:%02d:00', $m[3], $m[2], $m[1], $m[4], $m[5]);
        }
        
        // Try DD/MM/YYYY format
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $dtStr, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]} 00:00:00";
        }
        
        return date('Y-m-d H:i:s');
    }

    private function ensureDefaultWarehouse()
    {
        $wh = $this->db->table('warehouses')->where('code', 'KHO-CHINH')->get()->getRowArray();
        if ($wh) return $wh['id'];

        $this->db->table('warehouses')->insert([
            'code' => 'KHO-CHINH',
            'name' => 'Kho chính',
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->db->insertID();
    }

    private function importWarehouses()
    {
        CLI::write("=== Creating Default Warehouse ===", 'yellow');
        $this->ensureDefaultWarehouse();
        CLI::write("Default warehouse created", 'green');
    }
}
