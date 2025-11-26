ĐẶC TẢ NGHIỆP VỤ HOÀN CHỈNH - LanoCRM Cash Management & Order Workflow
Mục đích: Document này là nguồn chân lý duy nhất (Single Source of Truth) cho toàn bộ logic nghiệp vụ liên quan đến Sổ Quỹ, Quản lý Kho và Order Workflow trong hệ thống LanoCRM.
Audience: Backend developers, QA testers, Product owners
Last Updated: 2025-11-26
📋 MỤC LỤC
Tổng Quan Hệ Thống
Module Orders - Đơn Hàng
Module Returns - Trả Hàng
Module Purchase Orders - Nhập Hàng
Module Cash Management - Sổ Quỹ
Module Inventory - Quản Lý Kho
Payment Methods - Phương Thức Thanh Toán
Edge Cases & Business Rules
API Endpoints Summary
Testing Requirements
1. TỔNG QUAN HỆ THỐNG
1.1 Kiến Trúc Clean Architecture
Request → Controller → Service → Repository → Model → Database
          (routing)    (logic)   (queries)    (schema)
​
Golden Rules:
Controller: Chỉ routing, validation input, error handling
Service: Business logic, orchestration
Repository: Database queries only
Không mixed concerns giữa các layers
1.2 Luồng Dữ Liệu Chính
┌─────────────────┐
│  Purchase Order │ (Nhập hàng từ NCC)
│   (PO received) │
└────────┬────────┘
         │ ➕ CỘNG KHO
         │ 💰 PHIẾU CHI (nếu CASH)
         ↓
    ┌─────────┐
    │ WAREHOUSE│
    │ INVENTORY│
    └────┬────┘
         │
         │ ➖ TRỪ KHO (order processing)
         ↓
┌────────────────┐
│     Orders     │ (Bán hàng cho khách)
│  (completed)   │
└────────┬───────┘
         │ 💰 PHIẾU THU (nếu CASH, COD khác)
         ↓
┌────────────────┐
│    Returns     │ (Khách trả hàng)
│   (approved)   │
└────────┬───────┘
         │ 💰 PHIẾU CHI (nếu cash refund)
         │ ➕ HOÀN KHO (return completed)
         ↓
    ┌─────────┐
    │Cash Book│
    └─────────┘
​
2. MODULE ORDERS - ĐƠN HÀNG
2.1 Order Types & Status Flow
POS Orders (Bán tại quầy)
Status Flow: confirmed → completed (1 bước)

Actions:
- Tạo order → status = "completed" ngay
- ➖ TRỪ KHO ngay lập tức
- 💰 TẠO PHIẾU THU ngay lập tức (nếu CASH)
​
Quyết định: ✅ POS order = completed ngay (không qua processing)
SHIPPING Orders (Giao hàng)
Status Flow: confirmed → processing → shipping → delivered → completed

Actions:
- processing: ➖ TRỪ KHO
- completed: 💰 TẠO PHIẾU THU (chỉ CASH, COD khác)
​
2.2 Payment Methods Support
QUYẾT ĐỊNH QUAN TRỌNG: ✅ Support Multiple Payment Methods
Ví dụ: 1 order có thể thanh toán:
500,000đ = CASH
500,000đ = BANK_TRANSFER
Implementation:
Database Schema:
-- Table: order_payments (NEW)
CREATE TABLE order_payments (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    payment_method ENUM('CASH','BANK_TRANSFER','CARD','COD','EWALLET') NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    paid_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- orders.total = SUM(order_payments.amount)
-- orders.paid_amount = SUM(order_payments WHERE paid_at IS NOT NULL)
-- orders.debt_amount = orders.total - orders.paid_amount
​
Logic tạo phiếu thu:
// OrderService::complete() - khi order completed
foreach ($order['payments'] as $payment) {
    if ($payment['payment_method'] === 'CASH') {
        // Tạo phiếu thu riêng cho phần CASH
        $this->cashService->createReceipt([
            'amount' => $payment['amount'], // Chỉ số tiền CASH
            'reference_type' => 'order_payment',
            'reference_id' => $payment['id'],
            'description' => "Thu tiền đơn #{$order['order_number']} - Phần tiền mặt"
        ]);
    }
    // BANK/CARD/EWALLET → không tạo phiếu thu trong cash_transactions
}
​
2.3 COD Payment Handling
QUYẾT ĐỊNH: ⚠️ COD KHÔNG tạo phiếu thu tự động
Lý do: Tiền COD còn ở shipper, chưa về công ty
Flow:
1. Order completed với COD
   → KHÔNG tạo phiếu thu
   → orders.paid_amount = 0
   → orders.debt_amount = orders.total

2. Admin đối soát với shipper (manual)
   → POST /api/shipping/cod-settlement
   → TẠO PHIẾU THU lúc này
   → Cập nhật orders.paid_amount
​
Implementation (Module sau - SHIP-001):
// ShippingCODSettlementService::settleCOD()
public function settleCOD(array $orderIds, float $amountReceived): array
{
    $totalCOD = $this->calculateTotalCOD($orderIds);
    
    // Tạo phiếu thu
    $this->cashService->createReceipt([
        'category' => 'shipping_cod',
        'amount' => $amountReceived,
        'reference_type' => 'shipping_settlement',
        'description' => "Thu COD từ shipper - " . count($orderIds) . " đơn"
    ]);
    
    // Update orders.paid_amount
    foreach ($orderIds as $orderId) {
        $this->orderRepo->updatePaidAmount($orderId, ...);
    }
}
​
2.4 Partial Payment Support
QUYẾT ĐỊNH: ✅ Cho phép khách trả từng phần
Database Fields:
orders.total DECIMAL(15,2)         -- Tổng tiền đơn hàng
orders.paid_amount DECIMAL(15,2)   -- Đã trả
orders.debt_amount DECIMAL(15,2)   -- Còn nợ (computed: total - paid_amount)
orders.payment_status ENUM('unpaid','partial','paid','overpaid')
​
Logic:
// Khi khách trả tiền lần 1
POST /api/orders/{id}/payments
{
    "payment_method": "CASH",
    "amount": 500000
}

// Service logic
$order = $this->orderRepo->findById($id);
$newPaidAmount = $order['paid_amount'] + $amount;

if ($newPaidAmount > $order['total']) {
    throw new InvalidArgumentException('Overpaid');
}

// Tạo phiếu thu
$this->cashService->createReceipt([
    'amount' => $amount,
    'reference_type' => 'order_payment',
    'reference_id' => $paymentId
]);

// Update order
$this->orderRepo->update($id, [
    'paid_amount' => $newPaidAmount,
    'debt_amount' => $order['total'] - $newPaidAmount,
    'payment_status' => $this->calculatePaymentStatus($order['total'], $newPaidAmount)
]);
​
Payment Status Calculation:
private function calculatePaymentStatus(float $total, float $paid): string
{
    if ($paid <= 0) return 'unpaid';
    if ($paid >= $total) return 'paid';
    return 'partial';
}
​
3. MODULE RETURNS - TRẢ HÀNG
3.1 Return Flow
Status Flow: pending → approved → completed

Actions:
- approved (cash refund): 💰 TẠO PHIẾU CHI
- completed: ➕ HOÀN KHO
​
3.2 Return Window
QUYẾT ĐỊNH: ✅ 30 ngày kể từ khi order completed
// ReturnValidator::validateCreate()
$completedAt = strtotime($order['completed_at']);
$now = time();
$daysPassed = ($now - $completedAt) / 86400;

if ($daysPassed > 30) {
    throw new InvalidArgumentException('Return window expired (30 days)');
}
​
3.3 Refund Method
QUYẾT ĐỊNH: ✅ Hoàn tiền theo payment method gốc
// ReturnService::approve()
$originalPaymentMethod = $order['payment_method']; // Hoặc từ order_payments

if ($validated['refund_method'] === 'auto') {
    $refundMethod = $originalPaymentMethod; // CASH → CASH, BANK → BANK
}

// Chỉ tạo phiếu chi nếu refund = CASH
if ($refundMethod === 'CASH') {
    $this->cashService->createPayment([
        'category' => 'refund',
        'amount' => $refundAmount,
        'reference_type' => 'return_order',
        'reference_id' => $returnId
    ]);
}
​
Multi-payment order returns:
// Nếu order trả bằng CASH + BANK
// Return refund cũng tách:
foreach ($order['payments'] as $payment) {
    $refundAmount = ($payment['amount'] / $order['total']) * $return['return_amount'];
    
    if ($payment['payment_method'] === 'CASH') {
        // Tạo phiếu chi cho phần CASH
        $this->cashService->createPayment([
            'amount' => $refundAmount,
            'description' => "Hoàn tiền trả hàng - Phần tiền mặt"
        ]);
    }
}
​
4. MODULE PURCHASE ORDERS - NHẬP HÀNG
4.1 PO Status Flow
Status Flow: pending → received

Actions:
- received: ➕ CỘNG KHO
- received (CASH): 💰 TẠO PHIẾU CHI
​
4.2 Trigger: API Endpoint
QUYẾT ĐỊNH: ✅ API riêng POST /api/purchase-orders/{id}/receive
POST /api/purchase-orders/{id}/receive
Authorization: Bearer {token}

Request:
{
    "notes": "Hàng đã kiểm tra đầy đủ" // Optional
}

Response 200:
{
    "success": true,
    "data": {
        "id": 123,
        "po_number": "PO-20251126-001",
        "status": "received",
        "received_at": "2025-11-26 18:45:00",
        "received_by": 1,
        "cash_transaction_created": true
    }
}
​
4.3 Payment Logic
QUYẾT ĐỊNH:
✅ Chỉ CASH → tạo phiếu chi
✅ BANK/EWALLET → KHÔNG tạo (track riêng ở bank reconciliation)
✅ Số tiền = po.total (đã trừ discount)
✅ Branch bắt buộc (throw error nếu null)
✅ Status phiếu chi = approved (tự động)
// PurchaseOrderStatusService::markReceived()
if ($po['payment_method'] === 'CASH') {
    if (empty($po['branch_id'])) {
        throw new InvalidArgumentException('PO must have branch_id');
    }
    
    $supplier = $this->supplierRepo->findById($po['supplier_id']);
    
    $this->cashService->createPayment([
        'branch_id' => $po['branch_id'],
        'type' => 'payment',
        'category' => 'purchase',
        'amount' => $po['total'], // Số tiền thực tế
        'payment_method' => 'cash',
        'reference_type' => 'purchase_order',
        'reference_id' => $po['id'],
        'description' => sprintf(
            'Chi trả NCC %s - Đơn mua #%s',
            $supplier['name'],
            $po['po_number']
        ),
        'transaction_date' => date('Y-m-d H:i:s'),
        'status' => 'approved',
        'created_by' => $userId,
        'approved_by' => $userId,
        'approved_at' => date('Y-m-d H:i:s')
    ]);
}
​
4.4 Supplier Info
QUYẾT ĐỊNH: ✅ Lưu vào description, không duplicate data
Có thể query ngược: reference_type='purchase_order' → lấy PO → lấy supplier
Không cần thêm fields mới: supplier_name, bank_account, etc.
5. MODULE CASH MANAGEMENT - SỔ QUỸ
5.1 Cash Transaction Types
cash_transactions.type ENUM('receipt', 'payment')
​
Receipt (Phiếu thu):
Thu tiền từ khách (order completed)
Thu COD từ shipper (settlement)
Thu nhập khác
Payment (Phiếu chi):
Chi trả NCC (PO received)
Hoàn tiền khách (return approved)
Chi phí vận hành
Lương
Chi phí khác
5.2 Categories
// Receipt categories
const CATEGORY_SALES = 'sales';           // Bán hàng
const CATEGORY_REFUND = 'refund';         // Hoàn tiền (?)
const CATEGORY_DEPOSIT = 'deposit';       // Tiền cọc
const CATEGORY_SHIPPING_COD = 'shipping_cod'; // Thu COD từ shipper
const CATEGORY_OTHER_INCOME = 'other_income';

// Payment categories
const CATEGORY_PURCHASE = 'purchase';     // Mua hàng
const CATEGORY_REFUND = 'refund';         // Hoàn tiền khách
const CATEGORY_SALARY = 'salary';         // Lương
const CATEGORY_EXPENSE = 'expense';       // Chi phí
const CATEGORY_SHIPPING_FEE = 'shipping_fee'; // Phí ship
const CATEGORY_WITHDRAWAL = 'withdrawal'; // Rút tiền
const CATEGORY_OTHER_EXPENSE = 'other_expense';
​
5.3 Reference Types
const REFERENCE_ORDER = 'order';
const REFERENCE_ORDER_PAYMENT = 'order_payment'; // NEW - cho multi-payment
const REFERENCE_PURCHASE_ORDER = 'purchase_order';
const REFERENCE_RETURN_ORDER = 'return_order';
const REFERENCE_SHIPPING_SETTLEMENT = 'shipping_settlement'; // NEW - COD
const REFERENCE_MANUAL = 'manual';
​
5.4 Balance Calculation
Real-time, không cache:
// CashTransactionRepository::calculateBalance()
$receiptTotal = SUM(amount WHERE type='receipt' AND deleted_at IS NULL);
$paymentTotal = SUM(amount WHERE type='payment' AND deleted_at IS NULL);
$balance = $receiptTotal - $paymentTotal;
​
6. MODULE INVENTORY - QUẢN LÝ KHO
6.1 Stock Movements
Khi nào trừ/cộng kho:
Event
Inventory Action
Timing
PO received
➕ Cộng kho
Ngay lập tức
Order confirmed
🔒 Reserve (optional)
Optional
Order processing
➖ Trừ kho
Khi chuyển status
Order cancelled
➕ Hoàn kho
Nếu đã processing
Return completed
➕ Hoàn kho
Khi hoàn tất
6.2 Movement Logger
// InventoryMovementLogger::log()
[
    'product_id' => $productId,
    'variant_id' => $variantId,
    'warehouse_id' => $warehouseId,
    'movement_type' => 'OUT', // IN/OUT/TRANSFER/ADJUSTMENT
    'quantity' => -5,
    'reference_type' => 'order',
    'reference_id' => $orderId,
    'notes' => 'Trừ kho cho đơn hàng #ORD-001',
    'created_by' => $userId
]
​
7. PAYMENT METHODS - PHƯƠNG THỨC THANH TOÁN
7.1 Supported Methods
const PAYMENT_CASH = 'CASH';
const PAYMENT_BANK = 'BANK_TRANSFER';
const PAYMENT_CARD = 'CARD';
const PAYMENT_COD = 'COD';
const PAYMENT_EWALLET = 'EWALLET';
​
7.2 Cash Transaction Rules
Chỉ tạo cash_transaction khi:
✅ payment_method = 'CASH'
❌ BANK/CARD/EWALLET → Không tạo (track ở bank reconciliation)
⚠️ COD → Không tạo ngay (đợi settlement)
8. EDGE CASES & BUSINESS RULES
8.1 Order Cancellation
// Nếu order đã processing (đã trừ kho)
if ($order['status'] === 'processing' && $newStatus === 'cancelled') {
    // HOÀN KHO
    $this->inventoryService->restoreStock($order);
    
    // KHÔNG hoàn tiền tự động (admin xử lý manual)
}
​
8.2 Over-payment Prevention
// Khi thêm payment vào order
$newTotal = $order['paid_amount'] + $paymentAmount;
if ($newTotal > $order['total']) {
    throw new InvalidArgumentException('Payment exceeds order total');
}
​
8.3 Return Quantity Limit
// Không cho trả nhiều hơn đã mua
foreach ($returnItems as $item) {
    $purchased = $orderItem['quantity'];
    $returned = $this->getReturnedQuantity($orderItem['id']);
    
    if ($item['quantity'] + $returned > $purchased) {
        throw new InvalidArgumentException('Cannot return more than purchased');
    }
}
​
8.4 Stock Availability Check
// Trước khi trừ kho
$available = $this->inventoryRepo->getAvailableStock($productId, $warehouseId);
if ($available < $requiredQuantity) {
    // Cảnh báo NHƯNG vẫn cho tạo order (business decision)
    $warnings[] = "Low stock: {$productName}";
}
​
9. API ENDPOINTS SUMMARY
Orders
POST   /api/orders                    # Tạo order
GET    /api/orders                    # List orders
GET    /api/orders/{id}               # Chi tiết order
PUT    /api/orders/{id}/status        # Đổi status (ORD-003)
POST   /api/orders/{id}/payments      # Thêm payment (partial)
​
Returns
POST   /api/orders/{id}/return        # Tạo return request
PUT    /api/returns/{id}/approve      # Approve return (RETURN-002)
PUT    /api/returns/{id}/complete     # Complete return (hoàn kho)
​
Purchase Orders
POST   /api/purchase-orders            # Tạo PO
GET    /api/purchase-orders            # List PO
POST   /api/purchase-orders/{id}/receive  # Nhận hàng (PO-002)
​
Cash Management
POST   /api/cash/receipt              # Tạo phiếu thu
POST   /api/cash/payment              # Tạo phiếu chi
GET    /api/cash/transactions         # List transactions
GET    /api/cash/balance              # Số dư quỹ
GET    /api/cash/report/daily         # Báo cáo ngày
DELETE /api/cash/transactions/{id}    # Xóa transaction
​
Shipping COD (Future - SHIP-001)
POST   /api/shipping/cod-settlement   # Đối soát COD với shipper
GET    /api/shipping/cod-pending      # List orders COD chưa đối soát
​
10. TESTING REQUIREMENTS
10.1 Unit Tests (Service Layer)
OrderService:
testCreatePOSOrder() - POS order completed ngay
testCreateShippingOrder() - SHIPPING order qua processing
testCompleteOrderWithCash() - Tạo phiếu thu
testCompleteOrderWithCOD() - KHÔNG tạo phiếu thu
testMultiplePaymentMethods() - Multi-payment tạo nhiều phiếu thu
testPartialPayment() - Trả từng phần
ReturnService:
testApproveCashRefund() - Tạo phiếu chi
testApproveBankRefund() - Không tạo phiếu chi
testCompleteReturn() - Hoàn kho
testReturnWindowExpired() - Reject sau 30 ngày
PurchaseOrderStatusService:
testMarkReceivedWithCash() - Cộng kho + tạo phiếu chi
testMarkReceivedWithBank() - Cộng kho, không tạo phiếu chi
testMarkReceivedNoBranch() - Throw error
10.2 Integration Tests (API Layer)
E2E Flow Tests:
// Test: Mua hàng → Bán hàng → Trả hàng
testFullOrderLifecycle()
{
    // 1. Nhập hàng (PO)
    POST /api/purchase-orders/{id}/receive
    → Assert: inventory increased
    → Assert: cash payment created (if CASH)
    
    // 2. Tạo order
    POST /api/orders
    → Assert: order created
    
    // 3. Processing
    PUT /api/orders/{id}/status (processing)
    → Assert: inventory decreased
    
    // 4. Complete
    PUT /api/orders/{id}/status (completed)
    → Assert: cash receipt created (if CASH)
    
    // 5. Return
    POST /api/orders/{id}/return
    PUT /api/returns/{id}/approve
    → Assert: cash payment created (if cash refund)
    
    PUT /api/returns/{id}/complete
    → Assert: inventory restored
}
​
10.3 Coverage Requirements
Unit tests: ≥ 70%
Integration tests: ≥ 50%
Critical paths: 100% (order complete, PO receive, return approve)
📊 SUMMARY TABLE
Module
Status
Auto Cash Transaction
Auto Inventory
Orders (POS)
✅ Done
✅ Receipt (if CASH)
✅ Deduct on create
Orders (SHIPPING)
✅ Done
✅ Receipt on completed (not COD)
✅ Deduct on processing
Returns
✅ Done
✅ Payment on approved (if cash)
✅ Restore on completed
Purchase Orders
✅ Done
✅ Payment on received (if CASH)
✅ Increase on received
COD Settlement
⏳ Future (SHIP-001)
⏳ On manual settlement
N/A
Multi-payment
✅ Decided
✅ Multiple receipts
N/A
Partial Payment
✅ Decided
✅ Each payment = 1 receipt
N/A
🚀 IMPLEMENTATION PRIORITY
Phase 1 (Current Sprint)
✅ Implement multi-payment support (order_payments table)
✅ Implement partial payment API
✅ Update cash transaction logic for multi-payment
✅ Refund by original payment method
✅ Write tests for all above
Phase 2 (Next Sprint)
⏳ Shipping COD settlement module (SHIP-001)
⏳ Supplier debt tracking (DEBT-001)
⏳ Customer debt tracking (DEBT-002)
📞 CONTACT & QUESTIONS
Product Owner: Nguyễn Trung (shine391@gmail.com)
Tech Lead: [TBD]
Questions? Create issue in GitHub với label question hoặc clarification-needed
Document Version: 1.0
Status: ✅ APPROVED - READY FOR IMPLEMENTATION