# NHIỆM VỤ: Tăng/giảm giá hàng loạt cho bảng giá

## Mục tiêu
Thêm tính năng điều chỉnh giá hàng loạt cho tất cả items trong bảng giá.

**Ví dụ thực tế:**
- Tăng tất cả sản phẩm lên 10% (VD: 100k → 110k)
- Giảm cố định 50,000đ cho tất cả (VD: 200k → 150k)
- Áp dụng ngay cho 1000+ items trong 1 click

## Bối cảnh dự án
- Repository: https://github.com/shine391-org/kiotviet/tree/feature-price-lists
- Nhánh: feature-price-lists
- Tiếp nối: PROMPT 1, 2 đã hoàn thành

## Các loại điều chỉnh

### 1. Tăng theo phần trăm
- Operation: increase
- Type: percent
- Value: 10
- Công thức: price_new = price_old x 1.1

### 2. Giảm theo phần trăm
- Operation: decrease
- Type: percent
- Value: 15
- Công thức: price_new = price_old x 0.85

### 3. Tăng cố định
- Operation: increase
- Type: fixed
- Value: 50000
- Công thức: price_new = price_old + 50000

### 4. Giảm cố định
- Operation: decrease
- Type: fixed
- Value: 30000
- Công thức: price_new = price_old - 30000

### 5. Set giá mới
- Operation: set
- Type: fixed
- Value: 100000
- Công thức: price_new = 100000 (cho tất cả)

## Hướng dẫn thực hiện

### Bước 1: Thiết kẾ API endpoint

**Endpoint mới:** POST /api/price-lists/{id}/bulk-adjust

**Request body:**
{
    "operation": "increase",
    "type": "percent",
    "value": 10,
    "apply_rounding": true,
    "rounding_rule": "thousand",
    "filters": {
        "product_ids": [1, 2, 3],
        "min_price": 50000,
        "max_price": 500000
    }
}

**Response:**
{
    "success": true,
    "updated_count": 150,
    "preview": [
        {
            "product_id": 1,
            "old_price": 100000,
            "new_price": 110000,
            "change": 10000
        }
    ]
}

### Bước 2: Tạo BulkAdjustmentService

**File mới:** `backend-ci/app/Services/PriceLists/BulkAdjustmentService.php`

Tạo class với methods:

public function calculateNewPrice(float $oldPrice, array $adjustment): float
{
    $operation = $adjustment['operation'];
    $type = $adjustment['type'];
    $value = (float) $adjustment['value'];
    
    switch ($operation) {
        case 'increase':
            if ($type === 'percent') {
                return $oldPrice * (1 + $value / 100);
            } else {
                return $oldPrice + $value;
            }
        
        case 'decrease':
            if ($type === 'percent') {
                return $oldPrice * (1 - $value / 100);
            } else {
                return $oldPrice - $value;
            }
        
        case 'set':
            return $value;
        
        default:
            throw new InvalidArgumentException("Invalid operation: {$operation}");
    }
}

public function previewAdjustment(int $priceListId, array $adjustment): array
{
    // Lấy tất cả items
    $items = $this->repo->getItems($priceListId);
    
    // Apply filters nếu có
    if (isset($adjustment['filters'])) {
        $items = $this->applyFilters($items, $adjustment['filters']);
    }
    
    $preview = [];
    foreach ($items as $item) {
        $oldPrice = (float) $item['price'];
        $newPrice = $this->calculateNewPrice($oldPrice, $adjustment);
        
        // Apply rounding nếu có
        if (!empty($adjustment['apply_rounding'])) {
            $formulaService = new PriceFormulaService();
            $newPrice = $formulaService->applyRounding(
                $newPrice, 
                $adjustment['rounding_rule'] ?? 'none'
            );
        }
        
        $preview[] = [
            'product_id' => $item['product_id'],
            'variant_id' => $item['variant_id'],
            'old_price' => $oldPrice,
            'new_price' => max(0, $newPrice),
            'change' => $newPrice - $oldPrice,
            'change_percent' => $oldPrice > 0 ? (($newPrice - $oldPrice) / $oldPrice * 100) : 0,
        ];
    }
    
    return $preview;
}

public function applyAdjustment(int $priceListId, array $adjustment): int
{
    $preview = $this->previewAdjustment($priceListId, $adjustment);
    
    $db = $this->repo->db();
    $db->transBegin();
    
    try {
        $updated = 0;
        foreach ($preview as $change) {
            $this->repo->updateItemPrice(
                $priceListId,
                $change['product_id'],
                $change['variant_id'],
                $change['new_price']
            );
            $updated++;
        }
        
        $db->transCommit();
        return $updated;
        
    } catch (\Throwable $e) {
        $db->transRollback();
        throw $e;
    }
}

private function applyFilters(array $items, array $filters): array
{
    if (isset($filters['product_ids'])) {
        $items = array_filter($items, function($item) use ($filters) {
            return in_array($item['product_id'], $filters['product_ids']);
        });
    }
    
    if (isset($filters['min_price'])) {
        $items = array_filter($items, function($item) use ($filters) {
            return $item['price'] >= $filters['min_price'];
        });
    }
    
    if (isset($filters['max_price'])) {
        $items = array_filter($items, function($item) use ($filters) {
            return $item['price'] <= $filters['max_price'];
        });
    }
    
    return array_values($items);
}
### Bước 3: Thêm endpoint vào Controller

**File:** `backend-ci/app/Controllers/Api/PriceListsController.php`

Thêm methods:

public function bulkAdjustPreview($id)
{
    $data = $this->request->getJSON(true) ?? [];
    $service = new BulkAdjustmentService();
    
    return $this->wrap(function() use ($id, $data, $service) {
        $preview = $service->previewAdjustment((int)$id, $data);
        return $this->respond([
            'success' => true,
            'preview' => array_slice($preview, 0, 10),
            'total_items' => count($preview),
        ]);
    });
}

public function bulkAdjust($id)
{
    $data = $this->request->getJSON(true) ?? [];
    $service = new BulkAdjustmentService();
    
    return $this->wrap(function() use ($id, $data, $service) {
        $updated = $service->applyAdjustment((int)$id, $data);
        
        return $this->respond([
            'success' => true,
            'updated_count' => $updated,
            'message' => "Đã cập nhật {$updated} sản phẩm",
        ]);
    });
}

**Thêm routes:** `backend-ci/app/Config/Routes.php`

$routes->post('api/price-lists/(:num)/bulk-adjust-preview', 'PriceListsController::bulkAdjustPreview/$1');
$routes->post('api/price-lists/(:num)/bulk-adjust', 'PriceListsController::bulkAdjust/$1');

### Bước 4: Frontend - Modal điều chỉnh giá

**File:** `lanocrm/src/pages/price-lists/PriceListFormPage.jsx`

Thêm state và modal:

const [showBulkModal, setShowBulkModal] = useState(false);
const [bulkData, setBulkData] = useState({
    operation: 'increase',
    type: 'percent',
    value: 0,
    apply_rounding: false,
    rounding_rule: 'thousand',
});
const [preview, setPreview] = useState([]);

const handleBulkPreview = async () => {
    const response = await fetch(`/api/price-lists/${priceListId}/bulk-adjust-preview`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(bulkData),
    });
    const result = await response.json();
    setPreview(result.preview);
};

const handleBulkApply = async () => {
    if (!confirm('Xác nhận điều chỉnh giá cho tất cả sản phẩm?')) return;
    
    const response = await fetch(`/api/price-lists/${priceListId}/bulk-adjust`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(bulkData),
    });
    const result = await response.json();
    
    alert(result.message);
    setShowBulkModal(false);
    refreshItems();
};

Modal UI:

<Modal show={showBulkModal} onHide={() => setShowBulkModal(false)} size="lg">
    <Modal.Header>
        <Modal.Title>Điều chỉnh giá hàng loạt</Modal.Title>
    </Modal.Header>
    <Modal.Body>
        <div className="row">
            <div className="col-md-4">
                <label>Thao tác</label>
                <select 
                    className="form-control"
                    value={bulkData.operation}
                    onChange={(e) => setBulkData({...bulkData, operation: e.target.value})}
                >
                    <option value="increase">Tăng giá</option>
                    <option value="decrease">Giảm giá</option>
                    <option value="set">Đặt giá mới</option>
                </select>
            </div>
            
            <div className="col-md-4">
                <label>Loại</label>
                <select 
                    className="form-control"
                    value={bulkData.type}
                    onChange={(e) => setBulkData({...bulkData, type: e.target.value})}
                >
                    <option value="percent">Phần trăm</option>
                    <option value="fixed">Cố định</option>
                </select>
            </div>
            
            <div className="col-md-4">
                <label>Giá trị</label>
                <input 
                    type="number"
                    className="form-control"
                    value={bulkData.value}
                    onChange={(e) => setBulkData({...bulkData, value: e.target.value})}
                />
            </div>
        </div>
        
        <div className="form-check mt-3">
            <input 
                type="checkbox"
                checked={bulkData.apply_rounding}
                onChange={(e) => setBulkData({...bulkData, apply_rounding: e.target.checked})}
            />
            <label>Làm tròn giá</label>
        </div>
        
        <button className="btn btn-info mt-3" onClick={handleBulkPreview}>
            Xem trước
        </button>
        
        {preview.length > 0 && (
            <table className="table mt-3">
                <thead>
                    <tr>
                        <th>SP</th>
                        <th>Giá cũ</th>
                        <th>Giá mới</th>
                        <th>Thay đổi</th>
                    </tr>
                </thead>
                <tbody>
                    {preview.map((item, i) => (
                        <tr key={i}>
                            <td>{item.product_id}</td>
                            <td>{formatPrice(item.old_price)}</td>
                            <td className="text-success">{formatPrice(item.new_price)}</td>
                            <td>{formatPrice(item.change)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>
        )}
    </Modal.Body>
    <Modal.Footer>
        <button className="btn btn-secondary" onClick={() => setShowBulkModal(false)}>
            Hủy
        </button>
        <button className="btn btn-primary" onClick={handleBulkApply}>
            Áp dụng
        </button>
    </Modal.Footer>
</Modal>

Thêm button mở modal:

<button className="btn btn-warning" onClick={() => setShowBulkModal(true)}>
    Điều chỉnh giá hàng loạt
</button>

### Bước 5: Viết tests

**File:** `backend-ci/tests/Services/BulkAdjustmentServiceTest.php`

public function testIncreaseByPercent()
{
    $service = new BulkAdjustmentService();
    $oldPrice = 100000;
    $adjustment = [
        'operation' => 'increase',
        'type' => 'percent',
        'value' => 10,
    ];
    
    $newPrice = $service->calculateNewPrice($oldPrice, $adjustment);
    $this->assertEquals(110000, $newPrice);
}

public function testDecreaseByFixed()
{
    $service = new BulkAdjustmentService();
    $oldPrice = 200000;
    $adjustment = [
        'operation' => 'decrease',
        'type' => 'fixed',
        'value' => 50000,
    ];
    
    $newPrice = $service->calculateNewPrice($oldPrice, $adjustment);
    $this->assertEquals(150000, $newPrice);
}

public function testPreviewAdjustment()
{
    $service = new BulkAdjustmentService();
    $listId = $this->createPriceListWithItems(3);
    
    $adjustment = [
        'operation' => 'increase',
        'type' => 'percent',
        'value' => 10,
    ];
    
    $preview = $service->previewAdjustment($listId, $adjustment);
    
    $this->assertCount(3, $preview);
    $this->assertArrayHasKey('old_price', $preview[0]);
    $this->assertArrayHasKey('new_price', $preview[0]);
}

public function testApplyAdjustmentWithTransaction()
{
    $service = new BulkAdjustmentService();
    $listId = $this->createPriceListWithItems(100);
    
    $adjustment = [
        'operation' => 'increase',
        'type' => 'percent',
        'value' => 5,
    ];
    
    $updated = $service->applyAdjustment($listId, $adjustment);
    $this->assertEquals(100, $updated);
}
### Bước 6: Cập nhật tài liệu

**File:** `docs/tasks/new-modules/Price-list.md`

Thêm section:

## Điều Chỉnh Giá Hàng Loạt

### Tính năng
Thay đổi giá cho tất cả items trong bảng giá bằng 1 thao tác.

### Các loại điều chỉnh

1. Tăng theo phần trăm
   - Ví dụ: Tăng 10% cho tất cả
   - 100,000đ → 110,000đ

2. Giảm theo phần trăm
   - Ví dụ: Giảm 15% cho tất cả
   - 100,000đ → 85,000đ

3. Tăng cố định
   - Ví dụ: Tăng 50,000đ cho tất cả
   - 100,000đ → 150,000đ

4. Giảm cố định
   - Ví dụ: Giảm 30,000đ cho tất cả
   - 100,000đ → 70,000đ

5. Đặt giá mới
   - Ví dụ: Tất cả về 99,000đ
   - Mọi SP → 99,000đ

### Filters (tùy chọn)

Có thể áp dụng chỉ cho một số SP:
- product_ids: Chỉ điều chỉnh SP cụ thể
- min_price: Chỉ SP có giá >= X
- max_price: Chỉ SP có giá <= Y

### API Usage

Preview trước:
POST /api/price-lists/123/bulk-adjust-preview
Body: {"operation": "increase", "type": "percent", "value": 10}

Áp dụng:
POST /api/price-lists/123/bulk-adjust
Body: {"operation": "increase", "type": "percent", "value": 10}

### UI Flow

1. User click "Điều chỉnh giá hàng loạt"
2. Chọn operation, type, value
3. Click "Xem trước" → hiển thị 10 SP đầu
4. Xác nhận → áp dụng cho tất cả
5. Reload danh sách items

## Tiêu chí hoàn thành

- BulkAdjustmentService với calculateNewPrice()
- previewAdjustment() trả về danh sách thay đổi
- applyAdjustment() cập nhật DB với transaction
- Endpoint bulk-adjust-preview và bulk-adjust
- Frontend modal với form điều chỉnh
- Preview table hiển thị 10 items
- Filter theo product_ids, min_price, max_price
- Ít nhất 4 tests pass
- Documentation đầy đủ
- Commit: feat(price-lists): thêm điều chỉnh giá hàng loạt

## Lưu ý quan trọng

Performance:
- Test với 1000+ items
- Sử dụng transaction để đảm bảo atomicity
- Bulk update thay vì loop update từng item

Validation:
- Value phải > 0
- Decrease không được làm giá âm (min = 0)
- Preview trước khi apply (quan trọng!)

UX:
- Hiển thị số items sẽ bị ảnh hưởng
- Confirm dialog trước khi apply
- Loading state khi processing
- Toast success/error message

Business:
- Log audit: ai điều chỉnh, khi nào, bao nhiêu items
- Có thể rollback nếu cần (lưu history)
- Notification cho admin khi có bulk adjustment lớn

Security:
- Check permission trước khi cho phép bulk adjust
- Rate limit để tránh abuse
- Validate input để tránh injection

## Tham khảo
- KiotViet: Công cụ điều chỉnh giá hàng loạt
- Pattern: Batch processing với transaction
- Similar: Bulk update products, bulk delete
