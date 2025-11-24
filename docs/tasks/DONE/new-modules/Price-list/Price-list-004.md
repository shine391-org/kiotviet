# NHIỆM VỤ: Import giá từ file Excel

## Mục tiêu
Cho phép import hàng loạt giá cho bảng giá từ file Excel.

**Ví dụ thực tế:**
- Có 500 sản phẩm cần cập nhật giá
- Nhập tay mỗi SP mất 30s → tổng 4+ giờ
- Import Excel: 5 phút xong!

## Bối cảnh dự án
- Repository: https://github.com/shine391-org/kiotviet/tree/feature-price-lists
- Nhánh: feature-price-lists
- Tech: CodeIgniter 4 + PHPSpreadsheet

## Flow import

### 1. Download template
User download file Excel mẫu có cấu trúc:

| SKU | Tên SP | Giá hiện tại | Giá mới | Giảm % | Giảm cố định |
|-----|--------|--------------|---------|--------|--------------|
| SP001 | Sản phẩm A | 100,000 | 90,000 | 0 | 0 |
| SP002 | Sản phẩm B | 200,000 | 180,000 | 10 | 0 |

### 2. Upload file
User điền giá mới, upload file

### 3. Validate
- Check SKU có tồn tại không
- Check giá hợp lệ (>= 0)
- Check format đúng

### 4. Preview
Hiển thị danh sách sẽ import:
- Số row hợp lệ
- Số row lỗi (với lý do)

### 5. Confirm
User xác nhận → import vào DB

## Hướng dẫn thực hiện

### Bước 1: Cài đặt PHPSpreadsheet

**File:** `backend-ci/composer.json`

Thêm dependency:

"require": {
    "phpoffice/phpspreadsheet": "^1.29"
}

Chạy:
composer install

### Bước 2: Tạo ExcelImportService

**File mới:** `backend-ci/app/Services/PriceLists/ExcelImportService.php`

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelImportService
{
    public function generateTemplate(int $priceListId): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Header
        $sheet->setCellValue('A1', 'SKU');
        $sheet->setCellValue('B1', 'Tên sản phẩm');
        $sheet->setCellValue('C1', 'Giá hiện tại');
        $sheet->setCellValue('D1', 'Giá mới');
        $sheet->setCellValue('E1', 'Giảm giá %');
        $sheet->setCellValue('F1', 'Giảm giá cố định');
        
        // Style header
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE0E0E0');
        
        // Lấy items hiện tại (nếu có)
        $items = $this->getExistingItems($priceListId);
        $row = 2;
        
        foreach ($items as $item) {
            $sheet->setCellValue("A{$row}", $item['sku']);
            $sheet->setCellValue("B{$row}", $item['product_name']);
            $sheet->setCellValue("C{$row}", $item['current_price']);
            $sheet->setCellValue("D{$row}", ''); // để trống cho user điền
            $sheet->setCellValue("E{$row}", 0);
            $sheet->setCellValue("F{$row}", 0);
            $row++;
        }
        
        // Auto width
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        
        // Save to temp file
        $filename = 'price_list_template_' . $priceListId . '_' . time() . '.xlsx';
        $filepath = WRITEPATH . 'uploads/' . $filename;
        
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($filepath);
        
        return $filepath;
    }
    
    public function parseFile(string $filepath): array
    {
        if (!file_exists($filepath)) {
            throw new RuntimeException('File không tồn tại');
        }
        
        $spreadsheet = IOFactory::load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();
        
        // Skip header row
        array_shift($rows);
        
        $parsed = [];
        $errors = [];
        $rowNum = 2; // Excel row number (1 là header)
        
        foreach ($rows as $row) {
            $sku = trim($row[0] ?? '');
            $price = $row[3] ?? null;
            $discountPercent = $row[4] ?? 0;
            $discountAmount = $row[5] ?? 0;
            
            // Skip empty rows
            if (empty($sku)) {
                $rowNum++;
                continue;
            }
            
            // Validate
            $error = $this->validateRow($sku, $price, $discountPercent, $discountAmount);
            
            if ($error) {
                $errors[] = [
                    'row' => $rowNum,
                    'sku' => $sku,
                    'error' => $error,
                ];
            } else {
                $parsed[] = [
                    'sku' => $sku,
                    'price' => (float) $price,
                    'discount_percent' => (float) $discountPercent,
                    'discount_amount' => (float) $discountAmount,
                ];
            }
            
            $rowNum++;
        }
        
        return [
            'valid' => $parsed,
            'errors' => $errors,
            'total' => count($rows),
            'valid_count' => count($parsed),
            'error_count' => count($errors),
        ];
    }
    
    private function validateRow($sku, $price, $discountPercent, $discountAmount): ?string
    {
        // Check product exists
        $product = $this->productRepo->findBySku($sku);
        if (!$product) {
            return "SKU không tồn tại: {$sku}";
        }
        
        // Check price valid
        if ($price === null || $price === '') {
            return "Giá mới không được để trống";
        }
        
        if (!is_numeric($price) || $price < 0) {
            return "Giá không hợp lệ: {$price}";
        }
        
        // Check discount valid
        if (!is_numeric($discountPercent) || $discountPercent < 0 || $discountPercent > 100) {
            return "Giảm giá % phải từ 0-100";
        }
        
        if (!is_numeric($discountAmount) || $discountAmount < 0) {
            return "Giảm giá cố định phải >= 0";
        }
        
        return null; // No error
    }
    
    public function importToDatabase(int $priceListId, array $validItems): int
    {
        $db = $this->repo->db();
        $db->transBegin();
        
        try {
            $imported = 0;
            
            foreach ($validItems as $item) {
                $product = $this->productRepo->findBySku($item['sku']);
                
                $this->repo->upsertItem($priceListId, [
                    'product_id' => $product['id'],
                    'variant_id' => null,
                    'price' => $item['price'],
                    'discount_percent' => $item['discount_percent'],
                    'discount_amount' => $item['discount_amount'],
                ]);
                
                $imported++;
            }
            
            $db->transCommit();
            return $imported;
            
        } catch (\Throwable $e) {
            $db->transRollback();
            throw $e;
        }
    }
    
    private function getExistingItems(int $priceListId): array
    {
        // Query existing items with product info
        return $this->repo->getItemsWithProductInfo($priceListId);
    }
}
### Bước 3: Thêm endpoints vào Controller

**File:** `backend-ci/app/Controllers/Api/PriceListsController.php`

public function downloadTemplate($id)
{
    $service = new ExcelImportService();
    
    return $this->wrap(function() use ($id, $service) {
        $filepath = $service->generateTemplate((int)$id);
        
        return $this->response->download($filepath, null)->setFileName(
            'price_list_template.xlsx'
        );
    });
}

public function uploadImport($id)
{
    $file = $this->request->getFile('file');
    
    if (!$file->isValid()) {
        return $this->fail('File không hợp lệ');
    }
    
    $allowedTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
    if (!in_array($file->getMimeType(), $allowedTypes)) {
        return $this->fail('Chỉ chấp nhận file Excel (.xlsx, .xls)');
    }
    
    $filepath = $file->store('imports');
    $service = new ExcelImportService();
    
    return $this->wrap(function() use ($filepath, $service) {
        $result = $service->parseFile(WRITEPATH . 'uploads/' . $filepath);
        
        return $this->respond([
            'success' => true,
            'data' => $result,
            'file_path' => $filepath,
        ]);
    });
}

public function confirmImport($id)
{
    $data = $this->request->getJSON(true) ?? [];
    $filepath = $data['file_path'] ?? null;
    
    if (!$filepath) {
        return $this->fail('Thiếu file_path');
    }
    
    $service = new ExcelImportService();
    
    return $this->wrap(function() use ($id, $filepath, $service) {
        $fullPath = WRITEPATH . 'uploads/' . $filepath;
        $parsed = $service->parseFile($fullPath);
        $imported = $service->importToDatabase((int)$id, $parsed['valid']);
        
        unlink($fullPath);
        
        return $this->respond([
            'success' => true,
            'imported' => $imported,
            'message' => "Đã import thành công {$imported} sản phẩm",
        ]);
    });
}

**Thêm routes:**

$routes->get('api/price-lists/(:num)/download-template', 'PriceListsController::downloadTemplate/$1');
$routes->post('api/price-lists/(:num)/upload-import', 'PriceListsController::uploadImport/$1');
$routes->post('api/price-lists/(:num)/confirm-import', 'PriceListsController::confirmImport/$1');

### Bước 4: Frontend - Import UI

**File:** `lanocrm/src/pages/price-lists/PriceListFormPage.jsx`

const [importFile, setImportFile] = useState(null);
const [importPreview, setImportPreview] = useState(null);
const [showImportModal, setShowImportModal] = useState(false);

const handleDownloadTemplate = () => {
    window.open(`/api/price-lists/${priceListId}/download-template`, '_blank');
};

const handleFileSelect = (e) => {
    const file = e.target.files[0];
    if (file) {
        setImportFile(file);
    }
};

const handleUploadPreview = async () => {
    if (!importFile) {
        alert('Vui lòng chọn file');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', importFile);
    
    const response = await fetch(`/api/price-lists/${priceListId}/upload-import`, {
        method: 'POST',
        body: formData,
    });
    
    const result = await response.json();
    // Lưu cả file_path để confirm-import sử dụng
    setImportPreview({
        ...result.data,
        file_path: result.file_path,
    });
};

const handleConfirmImport = async () => {
    if (!importPreview) return;
    
    const response = await fetch(`/api/price-lists/${priceListId}/confirm-import`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ file_path: importPreview.file_path }),
    });
    
    const result = await response.json();
    alert(result.message);
    setShowImportModal(false);
    refreshItems();
};

Import Modal:

<Modal show={showImportModal} onHide={() => setShowImportModal(false)} size="lg">
    <Modal.Header>
        <Modal.Title>Import giá từ Excel</Modal.Title>
    </Modal.Header>
    <Modal.Body>
        <div className="mb-3">
            <button className="btn btn-outline-primary" onClick={handleDownloadTemplate}>
                <i className="fa fa-download"></i> Download Template
            </button>
            <small className="d-block mt-2 text-muted">
                Tải file mẫu, điền giá, rồi upload lại
            </small>
        </div>
        
        <div className="mb-3">
            <label>Chọn file Excel</label>
            <input 
                type="file"
                className="form-control"
                accept=".xlsx,.xls"
                onChange={handleFileSelect}
            />
        </div>
        
        <button className="btn btn-info" onClick={handleUploadPreview}>
            Xem trước
        </button>
        
        {importPreview && (
            <div className="mt-3">
                <div className="alert alert-info">
                    <strong>Kết quả:</strong>
                    <ul className="mb-0">
                        <li>Tổng dòng: {importPreview.total}</li>
                        <li className="text-success">Hợp lệ: {importPreview.valid_count}</li>
                        <li className="text-danger">Lỗi: {importPreview.error_count}</li>
                    </ul>
                </div>
                
                {importPreview.errors.length > 0 && (
                    <div className="alert alert-danger">
                        <strong>Các dòng lỗi:</strong>
                        <ul>
                            {importPreview.errors.map((err, i) => (
                                <li key={i}>
                                    Dòng {err.row}: {err.sku} - {err.error}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
                
                {importPreview.valid_count > 0 && (
                    <table className="table table-sm">
                        <thead>
                            <tr>
                                <th>SKU</th>
                                <th>Giá mới</th>
                                <th>Giảm %</th>
                                <th>Giảm cố định</th>
                            </tr>
                        </thead>
                        <tbody>
                            {importPreview.valid.slice(0, 5).map((item, i) => (
                                <tr key={i}>
                                    <td>{item.sku}</td>
                                    <td>{formatPrice(item.price)}</td>
                                    <td>{item.discount_percent}%</td>
                                    <td>{formatPrice(item.discount_amount)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </div>
        )}
    </Modal.Body>
    <Modal.Footer>
        <button className="btn btn-secondary" onClick={() => setShowImportModal(false)}>
            Hủy
        </button>
        {importPreview && importPreview.valid_count > 0 && (
            <button className="btn btn-primary" onClick={handleConfirmImport}>
                Import {importPreview.valid_count} sản phẩm
            </button>
        )}
    </Modal.Footer>
</Modal>

Button mở modal:

<button className="btn btn-success" onClick={() => setShowImportModal(true)}>
    <i className="fa fa-upload"></i> Import từ Excel
</button>

### Bước 5: Viết tests

**File:** `backend-ci/tests/Services/ExcelImportServiceTest.php`

public function testGenerateTemplate()
{
    $service = new ExcelImportService();
    $listId = $this->createPriceList();
    
    $filepath = $service->generateTemplate($listId);
    
    $this->assertFileExists($filepath);
    $this->assertStringEndsWith('.xlsx', $filepath);
    
    unlink($filepath);
}

public function testParseValidFile()
{
    $service = new ExcelImportService();
    $filepath = $this->createTestExcel();
    
    $result = $service->parseFile($filepath);
    
    $this->assertArrayHasKey('valid', $result);
    $this->assertArrayHasKey('errors', $result);
    $this->assertGreaterThan(0, $result['valid_count']);
}

public function testValidateRowInvalidSKU()
{
    $service = new ExcelImportService();
    $error = $service->validateRow('INVALID_SKU', 100000, 0, 0);
    
    $this->assertStringContainsString('không tồn tại', $error);
}

public function testImportToDatabase()
{
    $service = new ExcelImportService();
    $listId = $this->createPriceList();
    
    $items = [
        ['sku' => 'SP001', 'price' => 90000, 'discount_percent' => 0, 'discount_amount' => 0],
        ['sku' => 'SP002', 'price' => 180000, 'discount_percent' => 10, 'discount_amount' => 0],
    ];
    
    $this->seedProducts(['SP001', 'SP002']);
    
    $imported = $service->importToDatabase($listId, $items);
    
    $this->assertEquals(2, $imported);
}
### Bước 6: Cập nhật tài liệu

**File:** `docs/tasks/new-modules/Price-list.md`

## Import Giá Từ Excel

### Tính năng
Import hàng loạt giá cho bảng giá từ file Excel, tiết kiệm thời gian.

### Flow sử dụng

1. Download template
   - Click "Download Template"
   - Nhận file Excel với format chuẩn
   - Có sẵn danh sách SP hiện tại (nếu có)

2. Điền dữ liệu
   - Cột A: SKU (bắt buộc)
   - Cột B: Tên SP (tham khảo)
   - Cột C: Giá hiện tại (tham khảo)
   - Cột D: Giá mới (bắt buộc)
   - Cột E: Giảm giá % (tùy chọn)
   - Cột F: Giảm giá cố định (tùy chọn)

3. Upload file
   - Click "Chọn file Excel"
   - Chọn file vừa điền
   - Click "Xem trước"

4. Kiểm tra preview
   - Số dòng hợp lệ
   - Số dòng lỗi (với lý do chi tiết)
   - Xem trước 5 dòng đầu

5. Confirm import
   - Click "Import X sản phẩm"
   - Chờ xử lý
   - Nhận thông báo thành công

### Validation rules

SKU:
- Bắt buộc phải có
- Phải tồn tại trong hệ thống

Giá mới:
- Bắt buộc phải có
- Phải là số >= 0

Giảm giá %:
- Từ 0 đến 100
- Mặc định: 0

Giảm giá cố định:
- Phải >= 0
- Mặc định: 0

### Error handling

Lỗi phổ biến:
- SKU không tồn tại → Bỏ qua dòng đó
- Giá không hợp lệ → Báo lỗi dòng X
- File sai format → Báo lỗi ngay

Khi có lỗi:
- Hiển thị danh sách lỗi chi tiết
- Import chỉ các dòng hợp lệ
- Không import dòng nào nếu chọn "all or nothing"

### Technical

Library: PHPSpreadsheet
Format support: .xlsx, .xls
Max file size: 5MB (config)
Max rows: 5000 (config)
Transaction: Có (rollback nếu fail)

## Tiêu chí hoàn thành

- Cài đặt PHPSpreadsheet thành công
- generateTemplate() tạo file Excel đúng format
- parseFile() đọc và validate file
- Validation rules đầy đủ
- importToDatabase() với transaction
- Endpoint download-template
- Endpoint upload-import (preview)
- Endpoint confirm-import
- Frontend modal với 3 bước
- Preview hiển thị errors chi tiết
- Ít nhất 4 tests pass
- Documentation đầy đủ
- Commit: feat(price-lists): thêm import giá từ Excel

## Lưu ý quan trọng

Performance:
- Test với file 1000+ rows
- Chunk insert (mỗi lần 100 rows)
- Timeout: tăng execution time nếu cần
- Memory: ini_set('memory_limit', '256M')

Security:
- Validate file type (mime)
- Scan virus nếu production
- Giới hạn file size
- Delete temp file sau khi import

UX:
- Progress bar khi upload
- Clear error messages
- Preview trước khi import (quan trọng!)
- Undo option (nice to have)

Data integrity:
- Transaction đảm bảo all or nothing
- Validate SKU trước khi insert
- Check duplicate trong file
- Log import history (ai, khi nào, bao nhiêu)

Error recovery:
- Nếu import fail → rollback
- Keep temp file để retry
- Email admin nếu có lỗi nghiêm trọng

## Template structure

Sheet: PriceList

Header row (row 1):
A1: SKU (required)
B1: Tên sản phẩm (readonly)
C1: Giá hiện tại (readonly)
D1: Giá mới (required)
E1: Giảm giá % (optional)
F1: Giảm giá cố định (optional)

Data rows (từ row 2):
- SKU: text
- Giá: number format with thousand separator
- Discount: number

Styling:
- Header: bold, gray background
- Readonly columns: light gray
- Required columns: yellow highlight

## Tham khảo
- PHPSpreadsheet docs: https://phpspreadsheet.readthedocs.io
- KiotViet: Import hàng hóa từ Excel
- Pattern: Batch import với validation
