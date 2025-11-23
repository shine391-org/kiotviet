🔥 PROMPT 1: Công Thức Tính Giá Tự Động & Cập Nhật
text
# NHIỆM VỤ: Xây dựng hệ thống tính giá theo công thức

## Mục tiêu
Thêm tính năng tính giá tự động theo công thức + tự động cập nhật khi giá gốc thay đổi.

**Ví dụ thực tế:**
- Giá sỉ = Giá lẻ - 10% → công thức: "base * 0.9"
- Giá VIP = Giá chung - 50.000đ → công thức: "base - 50000"
- Khi Giá lẻ thay đổi từ 100k → 120k → Giá sỉ tự động update 90k → 108k

## Bối cảnh dự án
- Repository: https://github.com/shine391-org/kiotviet/tree/feature/price-lists
- Tech stack: CodeIgniter 4 + PHP 8.4
- Kiến trúc: Service → Repository → Model
- Nhánh làm việc: `feature/price-lists`

## Hướng dẫn thực hiện từng bước

### Bước 1: Nghiên cứu codebase hiện tại

**Đọc và hiểu các file sau:**

1. **File:** `backend-ci/app/Services/PriceLists/PriceCalculatorService.php`
   - Tìm method `getProductPrice()` - logic tính giá hiện tại đang làm gì?
   - Tìm method `applyPricing()` - cách áp dụng discount hiện tại
   - **Câu hỏi cần trả lời:**
     - Hiện tại tính giá bằng cách nào? (price + discount_percent + discount_amount)
     - Khi nào thì recalculate price?

2. **File:** `backend-ci/app/Services/PriceLists/PriceListService.php`
   - Tìm method `upsertItems()` - update items của bảng giá như thế nào?
   - **Câu hỏi:** Khi update items, có trigger event gì không?

3. **File:** `backend-ci/app/Database/Migrations/2025-11-23-000004_CreatePriceListTables.php`
   - Xem schema hiện tại của table `price_lists` và `price_list_items`
   - **Câu hỏi:** Các cột hiện tại là gì? Thiếu cột nào để lưu công thức?

### Bước 2: Thiết kế thay đổi database

**Thêm các cột mới vào table `price_lists`:**

| Tên cột | Kiểu dữ liệu | Mô tả | Ví dụ |
|---------|--------------|-------|-------|
| `formula` | TEXT NULL | Công thức tính giá | "base * 0.9" hoặc "base - 50000" |
| `base_price_list_id` | INT NULL | ID bảng giá gốc để lấy base price | 1, 2, 3... |
| `auto_update` | BOOLEAN | Tự động cập nhật khi giá gốc đổi | true/false |
| `rounding_rule` | ENUM | Quy tắc làm tròn | 'none', 'thousand', 'ten_thousand' |

**Nhiệm vụ:**
- Tạo file migration mới (đặt tên theo pattern: `2025-11-24-HHMMSS_ThemCongThucVaoBangGia.php`)
- Thêm foreign key constraint: `base_price_list_id` → `price_lists.id`
- Thêm index cho `base_price_list_id` để query nhanh
- Comment rõ ràng cho từng cột

**Gợi ý code migration:**
$this->forge->addColumn('db_price_lists', [
'formula' => [
'type' => 'TEXT',
'null' => true,
'comment' => 'Công thức tính giá (VD: base * 0.9)',
],
// ... các cột khác
]);

text

### Bước 3: Tạo service xử lý công thức

**Tạo file mới:** `backend-ci/app/Services/PriceLists/PriceFormulaService.php`

**Nhiệm vụ service này:**
- Parse công thức từ string thành các phép toán
- Tính giá từ công thức + giá gốc
- Kiểm tra công thức có hợp lệ không
- Làm tròn giá theo quy tắc

**Methods cần implement:**

/**

Phân tích công thức thành các tokens

Input: "base * 0.9 - 5000"

Output: ['base', '*', 0.9, '-', 5000]
*/
public function parseFormula(string $formula): array

/**

Tính giá từ công thức

Input: formula = "base * 0.9", basePrice = 100000

Output: 90000
*/
public function calculateFromFormula(string $formula, float $basePrice): float

/**

Kiểm tra công thức hợp lệ

Chỉ chứa: số, operators (+, -, *, /), từ khóa "base"

Không chia cho 0

Không có ký tự lạ hoặc nguy hiểm
*/
public function validateFormula(string $formula): bool

/**

Làm tròn giá theo quy tắc

Input: price = 123456, rule = 'thousand'

Output: 123000
*/
public function applyRounding(float $price, string $rule): float

text

**Gợi ý implementation:**
- Parse formula: có thể dùng regex hoặc tách chuỗi đơn giản
- Calculate: thay "base" = giá gốc, rồi eval() (CHÚ Ý: phải sanitize trước!)
- Validate: dùng regex kiểm tra chỉ có ký tự hợp lệ
- Rounding: 
  - `thousand`: `round($price / 1000) * 1000`
  - `ten_thousand`: `round($price / 10000) * 10000`

### Bước 4: Tích hợp vào PriceCalculatorService

**File cần sửa:** `backend-ci/app/Services/PriceLists/PriceCalculatorService.php`

**Sửa method `getProductPrice()`:**

// Sau khi tìm được bảng giá phù hợp ($applied):

if (isset($applied['formula']) && !empty($applied['formula'])) {
// CÓ CÔNG THỨC

text
// Bước 1: Lấy giá gốc
if ($applied['base_price_list_id']) {
    $basePrice = $this->layGiatuBangGiaGoc($applied['base_price_list_id'], $productId, $variantId);
} else {
    $basePrice = $product['selling_price']; // giá gốc từ sản phẩm
}

// Bước 2: Tính theo công thức
$formulaService = new PriceFormulaService();
$final = $formulaService->calculateFromFormula($applied['formula'], $basePrice);

// Bước 3: Làm tròn nếu có
if (!empty($applied['rounding_rule'])) {
    $final = $formulaService->applyRounding($final, $applied['rounding_rule']);
}
} else {
// KHÔNG CÓ CÔNG THỨC - dùng logic cũ
$final = $this->applyPricing($basePrice, $item);
}

text

**Câu hỏi cần suy nghĩ:**
- Nếu `base_price_list_id` không tồn tại thì làm sao?
- Công thức và discount có conflict không? Ưu tiên cái nào?
- Có nên cache kết quả tính toán không?

### Bước 5: Xây dựng tính năng tự động cập nhật

**File cần sửa:** `backend-ci/app/Services/PriceLists/PriceListService.php`

**Thêm method mới:**

/**

Kích hoạt cập nhật tự động cho các bảng giá phụ thuộc

@param int $priceListId ID bảng giá gốc vừa thay đổi

@return array Danh sách IDs đã được cập nhật
*/
public function triggerAutoUpdate(int $priceListId): array
{
// Bước 1: Tìm tất cả bảng giá phụ thuộc
// WHERE base_price_list_id = $priceListId AND auto_update = true
$dependents = $this->repo->findDependentLists($priceListId);

$updated = [];
foreach ($dependents as $dependent) {
// Bước 2: Tính lại tất cả items trong bảng giá này
$count = $this->recalculateItems($dependent['id']);
$updated[] = $dependent['id'];

text
 // Bước 3: Đệ quy - update các bảng giá phụ thuộc của nó
 $nested = $this->triggerAutoUpdate($dependent['id']);
 $updated = array_merge($updated, $nested);
}

return array_unique($updated);
}

/**

Tính lại tất cả items trong 1 bảng giá
*/
private function recalculateItems(int $priceListId): int
{
// Bước 1: Lấy thông tin bảng giá (formula, base_price_list_id)
$priceList = $this->repo->findById($priceListId);

// Bước 2: Lấy tất cả items
$items = $this->repo->getItems($priceListId);

// Bước 3: Với mỗi item
$updated = 0;
foreach ($items as $item) {
// - Lấy giá gốc
// - Áp dụng công thức
// - Update giá mới vào DB
$updated++;
}

return $updated;
}

text

**Hook vào method `upsertItems()`:**

public function upsertItems(int $id, array $items): array
{
// Code cũ: insert/update items
$result = $this->repo->replaceItems($id, $validated);

text
// THÊM MỚI: Kích hoạt auto-update
$updated = $this->triggerAutoUpdate($id);

return [
    'success' => true,
    'inserted' => $result,
    'dependents_updated' => count($updated), // số bảng giá phụ thuộc đã update
    'updated_list_ids' => $updated,
];
}

text

### Bước 6: Thêm validation

**File cần sửa:** `backend-ci/app/Validators/PriceListValidator.php`

**Thêm các quy tắc validation:**

// 1. Kiểm tra syntax công thức
if (isset($data['formula']) && !empty($data['formula'])) {
$formulaService = new PriceFormulaService();
if (!$formulaService->validateFormula($data['formula'])) {
throw new InvalidArgumentException('Công thức không hợp lệ. Chỉ được dùng: base, +, -, *, /, số');
}
}

// 2. Kiểm tra circular reference (A → B → A)
if (isset($data['base_price_list_id'])) {
$this->checkCircularReference($currentId, $data['base_price_list_id']);
}

// 3. auto_update=true phải có base_price_list_id
if (!empty($data['auto_update']) && empty($data['base_price_list_id'])) {
throw new InvalidArgumentException('Bật auto_update phải chọn bảng giá gốc');
}

text

**Method kiểm tra circular reference:**

private function checkCircularReference(int $currentId, int $baseId): void
{
$visited = [];
$checkId = $baseId;

text
// Duyệt chuỗi: base → base.base → base.base.base...
while ($checkId !== null) {
    if ($checkId === $currentId) {
        throw new InvalidArgumentException('Phát hiện circular reference: bảng giá tham chiếu lẫn nhau');
    }
    
    if (in_array($checkId, $visited)) {
        break; // đã duyệt rồi
    }
    
    $visited[] = $checkId;
    $priceList = $this->repo->findById($checkId);
    $checkId = $priceList['base_price_list_id'] ?? null;
}
}

text

### Bước 7: Viết tests

**Tạo file mới:** `backend-ci/tests/Services/PriceFormulaServiceTest.php`

**Minimum 10 test cases:**

// 1. Parse công thức đơn giản
public function testParseSimpleFormula()
{
$result = $this->service->parseFormula('base * 0.9');
$this->assertEquals(['base', '*', 0.9], $result);
}

// 2. Tính toán từ công thức
public function testCalculateFromFormula()
{
// base = 100,000đ, công thức "base * 0.9"
$result = $this->service->calculateFromFormula('base * 0.9', 100000);
$this->assertEquals(90000, $result);
}

// 3. Công thức sai syntax
public function testInvalidFormulaSyntax()
{
$this->expectException(InvalidArgumentException::class);
$this->service->validateFormula('base ++ 10');
}

// 4. Chia cho 0
public function testDivisionByZero()
{
$this->expectException(RuntimeException::class);
$this->service->calculateFromFormula('base / 0', 100000);
}

// 5. Làm tròn nghìn
public function testRoundingThousand()
{
$result = $this->service->applyRounding(123456, 'thousand');
$this->assertEquals(123000, $result);
}

// 6. Làm tròn chục nghìn
public function testRoundingTenThousand()
{
$result = $this->service->applyRounding(123456, 'ten_thousand');
$this->assertEquals(120000, $result);
}

// 7. Phát hiện circular reference
public function testCircularReference()
{
// Tạo: List A (base=B), List B (base=A)
$this->expectException(InvalidArgumentException::class);
// ... code test
}

// 8. Auto-update chuỗi A → B → C
public function testAutoUpdateChain()
{
// Tạo: A → B → C (all auto_update=true)
// Update A → assert B và C đều update
}

// 9. Auto-update=false không update
public function testAutoUpdateRespectsFalseFlag()
{
// List B có auto_update=false
// Update A → assert B KHÔNG update
}

// 10. Công thức phức tạp
public function testComplexFormula()
{
// "base * 0.85 - 10000"
$result = $this->service->calculateFromFormula('base * 0.85 - 10000', 200000);
$this->assertEquals(160000, $result); // 200k * 0.85 - 10k = 160k
}

text

### Bước 8: Cập nhật tài liệu

**File cần sửa:** `docs/tasks/new-modules/Price-list.md`

**Thêm section mới:**

Công Thức Tính Giá Tự Động
Cú pháp công thức
Từ khóa: base (đại diện cho giá gốc)

Toán tử: +, -, *, /

Ví dụ:

base * 0.9 - giảm 10%

base - 50000 - giảm 50,000đ

base * 0.85 + 5000 - giảm 15% rồi cộng 5,000đ

base * 1.2 - tăng 20%

Tự động cập nhật
Khi bảng giá gốc thay đổi → bảng giá phụ thuộc tự động cập nhật

Điều kiện: flag auto_update = true

Hỗ trợ chuỗi phụ thuộc: A → B → C → D

Quy tắc làm tròn
thousand (nghìn): 123,456đ → 123,000đ

ten_thousand (chục nghìn): 123,456đ → 120,000đ

hundred (trăm): 123,456đ → 123,500đ

Lưu ý
Không được tạo circular reference (A → B → A)

Công thức chỉ chứa: số, base, toán tử cơ bản

Giá sau công thức luôn >= 0

text

## Tiêu chí hoàn thành

Đánh dấu ✅ khi hoàn thành:

- [x] Migration chạy thành công, thêm 4 cột mới vào `price_lists`
- [x] `PriceFormulaService` parse và calculate đúng với mọi công thức hợp lệ
- [x] Auto-update hoạt động với nested dependencies (A → B → C)
- [x] Circular reference bị phát hiện và reject
- [x] Rounding rules hoạt động chính xác
- [x] Ít nhất 10 tests pass 100%
- [x] Documentation đầy đủ với ví dụ cụ thể
- [x] Commit message: `feat(price-lists): thêm công thức tính giá tự động`

## Gợi ý và lưu ý

**Testing với data thật:**
- Giá gốc: 100,000đ
- Công thức: "base * 0.87" → kết quả: 87,000đ
- Làm tròn nghìn → 87,000đ (không đổi vì đã tròn)

**Performance:**
- Test với 50 bảng giá phụ thuộc × 1,000 items mỗi bảng
- Phải hoàn thành trong < 5 giây
- Sử dụng transaction để đảm bảo data consistency

**Logging:**
- Log mọi auto-update để audit: bảng giá nào update, bao nhiêu items, ai trigger
- Format: `[Auto-update] Base list #1 → Updated lists: #2, #3, #4 (150 items total)`

**Error handling:**
- Nếu công thức tính ra số âm → set giá = 0
- Nếu base_price_list không tồn tại → fallback về product.selling_price
- Nếu update chain fail → rollback toàn bộ (transaction)

## Tham khảo
- KiotViet docs: https://www.kiotviet.vn/huong-dan-su-dung-kiotviet/retail-hang-hoa/thiet-lap-gia/
- Pattern tham khảo: `backend-ci/app/Services/Inventory/InventoryService.php`
- Validator pattern: `backend-ci/app/Validators/InventoryValidator.php`
