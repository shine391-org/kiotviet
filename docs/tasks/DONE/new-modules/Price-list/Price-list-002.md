# NHIỆM VỤ: Hiển thị lợi nhuận và cảnh báo bán lỗ

## Mục tiêu
Thêm tính năng so sánh giá bán với giá vốn, hiển thị % lợi nhuận, và cảnh báo khi bán lỗ.

**Ví dụ thực tế:**
- Sản phẩm A: giá vốn 80k, giá bán 100k → lợi nhuận 25% ✅
- Sản phẩm B: giá vốn 120k, bảng giá VIP 100k → bán lỗ -16.7% ⚠️
- Hiển thị ngay trên UI để nhân viên biết trước khi tạo bảng giá

## Bối cảnh dự án
- Repository: https://github.com/shine391-org/kiotviet/tree/feature/price-lists
- Nhánh làm việc: `feature/price-lists`
- Tiếp nối: PROMPT 1 (Formula Engine) đã hoàn thành

## Hướng dẫn thực hiện từng bước

### Bước 1: Nghiên cứu schema sản phẩm hiện tại

**Câu hỏi cần trả lời:**
- Table `products` có cột nào lưu giá vốn không?
  - Có thể là: `cost_price`, `purchase_price`, `base_cost`, `cost`
- Nếu không có → cần thêm cột mới
- Giá vốn có được cập nhật từ phiếu nhập hàng không?

**Nhiệm vụ:**
1. Đọc file: `backend-ci/app/Models/ProductModel.php`
2. Đọc migration: tìm file tạo table `products`
3. Kiểm tra schema (chạy SQL hoặc xem migration)

**Ghi chú kết quả:**
- Tìm thấy cột: cost_price / purchase_price / ???
- Kiểu dữ liệu: DECIMAL(12,2)
- Có index không: ???
### Bước 2: Thêm/Kiểm tra cột giá vốn

**Nếu chưa có cột giá vốn, tạo migration:**

File: `2025-11-24-HHMMSS_ThemGiaVonVaoSanPham.php`

public function up()
{
$this->forge->addColumn('db_products', [
'cost_price' => [
'type' => 'DECIMAL',
'constraint' => '12,2',
'default' => 0,
'null' => false,
'comment' => 'Giá vốn/giá nhập',
'after' => 'selling_price',
],
]);
}

text

**Nếu đã có:** Kiểm tra tên cột và đảm bảo có dữ liệu

### Bước 3: Cập nhật PriceCalculatorService

**File:** `backend-ci/app/Services/PriceLists/PriceCalculatorService.php`

**Sửa method `getProductPrice()`:**

Thêm vào cuối method, trước return:

// THÊM MỚI: Lấy giá vốn
$product = $this->products->findById($productId);
$costPrice = 0;

if ($variantId) {
$variant = $this->variants->findById($variantId);
$costPrice = (float) ($variant['cost_price'] ?? $product['cost_price'] ?? 0);
} else {
$costPrice = (float) ($product['cost_price'] ?? 0);
}

// Tính lợi nhuận
$profit = $final - $costPrice;
$profitMargin = $costPrice > 0 ? (($final - $costPrice) / $costPrice) * 100 : 0;
$isLoss = $final < $costPrice;

return [
'success' => true,
'base_price' => $basePrice,
'final_price' => $final,
'line_total' => $final * $quantity,

text
// THÊM: Thông tin cost và profit
'cost_price' => $costPrice,
'profit' => round($profit, 2),
'profit_margin' => round($profitMargin, 2),
'is_loss' => $isLoss,
'warning' => $isLoss ? 'Cảnh báo: Giá bán thấp hơn giá vốn!' : null,

'applied_price_list_id' => $applied['id'] ?? null,
'applied_price_list_name' => $applied['name'] ?? null,
];


### Bước 4: Thêm method kiểm tra bán lỗ

**File:** `backend-ci/app/Services/PriceLists/PriceListService.php`

**Thêm method:**

/**

Kiểm tra items có bán lỗ không
*/
public function checkLossItems(array $items, bool $throwOnLoss = false): array
{
$lossItems = [];

foreach ($items as $item) {
$productId = $item['product_id'];
$variantId = $item['variant_id'] ?? null;
$price = (float) $item['price'];

 // Lấy giá vốn
 $product = $this->productRepo->findById($productId);
 $costPrice = 0;
 
 if ($variantId) {
     $variant = $this->variantRepo->findById($variantId);
     $costPrice = (float) ($variant['cost_price'] ?? $product['cost_price'] ?? 0);
 } else {
     $costPrice = (float) ($product['cost_price'] ?? 0);
 }
 
 // Kiểm tra bán lỗ
 if ($price < $costPrice && $costPrice > 0) {
     $lossItems[] = [
         'product_id' => $productId,
         'variant_id' => $variantId,
         'product_name' => $product['name'],
         'price' => $price,
         'cost_price' => $costPrice,
         'loss' => $costPrice - $price,
         'loss_percent' => round((($costPrice - $price) / $costPrice) * 100, 2),
     ];
 }
}

if ($throwOnLoss && count($lossItems) > 0) {
throw new RuntimeException(
'Phát hiện ' . count($lossItems) . ' sản phẩm bán lỗ'
);
}

return $lossItems;
}


**Hook vào `upsertItems()`:**

public function upsertItems(int $id, array $items): array
{
$validated = $this->validator->validateItems($items);

// THÊM: Kiểm tra bán lỗ
$lossItems = $this->checkLossItems($validated, false);

$result = $this->repo->replaceItems($id, $validated);
$updated = $this->triggerAutoUpdate($id);

return [
    'success' => true,
    'inserted' => $result,
    'dependents_updated' => count($updated),
    'loss_items_count' => count($lossItems),
    'loss_items' => $lossItems,
    'warning' => count($lossItems) > 0 ? 
        'Có ' . count($lossItems) . ' sản phẩm đang bán lỗ!' : null,
];
}

### Bước 5: Thêm config chính sách bán lỗ

**Tạo/sửa file:** `backend-ci/app/Config/PriceList.php`

public string $lossSalePolicy = 'warn';
public float $maxLossPercent = 10.0;

Options:
- 'allow': Cho phép bán lỗ, chỉ warning
- 'warn': Cảnh báo nếu vượt maxLossPercent
- 'block': Chặn hoàn toàn không cho lưu

### Bước 6: Frontend hiển thị profit

**File:** `lanocrm/src/pages/price-lists/PriceListFormPage.jsx`

Thêm hàm tính profit:

const calculateProfit = (item) => {
    const profit = item.price - item.cost_price;
    const profitMargin = item.cost_price > 0 
        ? ((profit / item.cost_price) * 100).toFixed(2)
        : 0;
    const isLoss = item.price < item.cost_price;
    return { profit, profitMargin, isLoss };
};

Thêm cột trong table:

<th>Giá vốn</th>
<th>Giá bán</th>
<th>Lợi nhuận</th>
<th>% LN</th>
<th>Trạng thái</th>

Render dòng với highlight:

<tr className={isLoss ? 'table-danger' : ''}>
    <td>{formatPrice(item.cost_price)}</td>
    <td>
        <input 
            type="number"
            value={item.price}
            onChange={(e) => updatePrice(index, e.target.value)}
            className={isLoss ? 'form-control is-invalid' : 'form-control'}
        />
    </td>
    <td className={profit < 0 ? 'text-danger' : 'text-success'}>
        {formatPrice(profit)}
    </td>
    <td className={profitMargin < 0 ? 'text-danger' : 'text-success'}>
        {profitMargin}%
    </td>
    <td>
        {isLoss ? (
            <span className="badge badge-danger">⚠️ Bán lỗ</span>
        ) : (
            <span className="badge badge-success">✓ OK</span>
        )}
    </td>
</tr>

Thêm thống kê tổng:

<div className="alert alert-info">
    <strong>Thống kê:</strong>
    <ul>
        <li>Tổng items: {items.length}</li>
        <li>Items có lãi: {items.filter(i => calculateProfit(i).profit >= 0).length}</li>
        <li className="text-danger">
            Items bán lỗ: {items.filter(i => calculateProfit(i).isLoss).length}
        </li>
    </ul>
</div>

### Bước 7: Viết tests

**File:** `backend-ci/tests/Services/PriceCalculatorServiceTest.php`

Test 1 - Tính profit:

public function testProfitCalculation()
{
    $productId = $this->seedProduct(100000, 80000);
    $price = $this->service->getProductPrice($productId, null, null);
    
    $this->assertEquals(80000, $price['cost_price']);
    $this->assertEquals(100000, $price['final_price']);
    $this->assertEquals(20000, $price['profit']);
    $this->assertEquals(25.0, $price['profit_margin']);
    $this->assertFalse($price['is_loss']);
}

Test 2 - Cảnh báo bán lỗ:

public function testLossSaleWarning()
{
    $productId = $this->seedProduct(150000, 120000);
    $listId = $this->seedPriceList(['name' => 'Sale', 'priority' => 5]);
    $this->seedItem($listId, $productId, null, 100000);
    
    $price = $this->service->getProductPrice($productId, null, null);
    
    $this->assertEquals(120000, $price['cost_price']);
    $this->assertEquals(100000, $price['final_price']);
    $this->assertTrue($price['is_loss']);
    $this->assertStringContainsString('Cảnh báo', $price['warning']);
}

Test 3 - Check loss items:

public function testCheckLossItemsInPriceList()
{
    $service = new PriceListService();
    $items = [
        ['product_id' => 1, 'price' => 90000],
        ['product_id' => 2, 'price' => 100000],
    ];
    
    $this->seedProduct(1, 100000, 80000);
    $this->seedProduct(2, 150000, 120000);
    
    $lossItems = $service->checkLossItems($items, false);
    
    $this->assertCount(1, $lossItems);
    $this->assertEquals(2, $lossItems[0]['product_id']);
}

Helper method:

private function seedProduct(int $id, float $sellingPrice, float $costPrice): int
{
    $this->db->table('db_products')->insert([
        'id' => $id,
        'code' => 'P' . $id,
        'name' => 'Product ' . $id,
        'selling_price' => $sellingPrice,
        'cost_price' => $costPrice,
        'created_at' => date('Y-m-d H:i:s'),
    ]);
    return $id;
}
### Bước 8: Cập nhật tài liệu

**File:** `docs/tasks/new-modules/Price-list.md`

Thêm section:

## So Sánh Giá Bán Với Giá Vốn

### Tính năng
- Hiển thị lợi nhuận (số tiền và phần trăm) cho mỗi item
- Cảnh báo màu đỏ khi giá bán thấp hơn giá vốn
- Chính sách bán lỗ có thể cấu hình

### Công thức
- Lợi nhuận = Giá bán - Giá vốn
- Phần trăm lợi nhuận = (Lợi nhuận / Giá vốn) x 100

### Ví dụ
- Giá vốn: 80,000đ
- Giá bán: 100,000đ
- Lợi nhuận: 20,000đ (25 phần trăm)

### Chính sách bán lỗ (Config)

1. Allow: Cho phép bán lỗ, chỉ hiển thị warning
2. Warn: Cho phép bán lỗ tối đa 10 phần trăm, vượt thì block
3. Block: Hoàn toàn không cho phép bán lỗ

### UI
- Dòng màu đỏ: item bán lỗ
- Badge "Bán lỗ": cảnh báo rõ ràng
- Thống kê: số items bán lỗ, LN trung bình

## Tiêu chí hoàn thành

- Table products có cột cost_price
- getProductPrice() trả về cost, profit, profit_margin, is_loss
- checkLossItems() kiểm tra items bán lỗ
- Config cho loss policy (allow/warn/block)
- Frontend hiển thị cột Giá vốn, LN, phần trăm LN, Trạng thái
- Dòng bán lỗ highlight màu đỏ
- Ít nhất 4 tests pass
- Documentation đầy đủ
- Commit: feat(price-lists): thêm so sánh giá vốn và cảnh báo bán lỗ

## Lưu ý quan trọng

Data integrity:
- Đảm bảo cost_price được cập nhật từ phiếu nhập hàng
- Cost_price = 0 thì không tính profit (tránh chia cho 0)

Performance:
- Với 1000+ items, cache cost_price
- Bulk fetch products thay vì query từng cái

UX:
- Cho phép override warning (checkbox)
- Log audit khi có người bán lỗ

Business rules:
- Khuyến mãi, thanh lý có thể bán lỗ
- Sản phẩm chiến lược có thể whitelist

## Tham khảo
- KiotViet: Báo cáo lãi/lỗ theo sản phẩm
- Pattern: Calculation service với validation
