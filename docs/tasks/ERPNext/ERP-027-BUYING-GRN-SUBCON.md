ERP-027 - Mua hàng: PO, Nhập kho, Landed Cost, Thầu phụ
Bạn là AI backend engineer phụ trách mảng mua hàng theo chuẩn ERPNext.

1. Bối cảnh
- ERPNext có Purchase Order, Receipt (Stock Entry), Landed Cost Voucher, Subcontracting. LanoCRM chưa có đầy đủ.

2. Phạm vi & Deliverables
- Migration: purchase_orders (number, supplier, status), purchase_order_items; goods_receipts (GRN) hoặc stock_entry kiểu receipt với reference PO; landed_cost_vouchers và lines phân bổ chi phí; subcontracting_orders (PO có thành phẩm + NVL cấp phát).
- Validator: PurchaseOrderValidator, GoodsReceiptValidator, LandedCostValidator, SubcontractingValidator.
- Repository: PurchaseOrderRepository (+ items), GoodsReceiptRepository, LandedCostRepository, SubcontractingRepository.
- Service: PurchaseOrderService (create/submit/receive/cancel), GoodsReceiptService (nhập kho từ PO, cập nhật ledger/bin), LandedCostService (phân bổ chi phí vào giá vốn), SubcontractingService (cấp phát NVL, nhận thành phẩm), numbering helpers.
- Controller API: CRUD/submit/cancel PO; tạo GRN từ PO; tạo landed cost; tạo/hoàn thành subcontracting.
- Integration: StockLedgerService cho nhập kho, cập nhật cost; ApprovalService cho PO lớn; AccountingService/GL hook khi cần.

3. Testing (DevDatabaseTrait)
- Unit: PO/GRN/LandedCost/Subcontracting service tests (status flow, cost allocation, ledger cập nhật), repo tests.
- Integration: API tạo PO → nhận hàng → phân bổ landed cost; subcontracting cấp phát/nhận thành phẩm.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- PO/GRN/LCV/Subcontracting vận hành, ghi ledger/bin đúng, cost được phân bổ.
- Unit + integration tests pass.
