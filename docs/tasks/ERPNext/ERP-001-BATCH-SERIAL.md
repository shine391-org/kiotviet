ERP-001 - Batch/Serial/Expiry Tracking
Bạn là AI backend engineer phụ trách đưa batch/serial/expiry vào LanoCRM theo chuẩn ERPNext.

1. Bối cảnh
- Gap lớn nhất so với ERPNext: thiếu batch, serial number, expiry, warranty; ảnh hưởng tồn kho và fulfillment.
- Phase 1 ưu tiên Inventory/Fulfillment.
- Tham chiếu: AGENTS.md, docs/tasks/IMPLEMENTATION-PLAN-ERPNEXT-FEATURES.md, docs/analysis/ERPNEXT-LANOCRM-COMPARISON.md, ERPNext batch doctype `erpnext/stock/doctype/batch/batch.json`.

2. Phạm vi & Deliverables
- Migration: product_batches (batch_number, mfg_date, expiry_date, initial/current_qty, cost_per_unit, supplier/ref doc), product_serial_numbers (serial_number, status, batch_id optional, warranty_expiry, sold_to_order_id, sold_date).
- Validator: ProductBatchValidator, ProductSerialValidator.
- Repository: ProductBatchRepository (CRUD, find by product/batch, adjust qty, expiring batches), ProductSerialNumberRepository (CRUD, find available, reserve/update status).
- Service: ProductBatchService (create/update, adjust quantity, list/expiring), ProductSerialNumberService (create, sell/return/reserve, list available), tích hợp với inventory movement hooks.
- Controller API (thin): resource-style batches & serials; endpoint adjust-quantity; reserve serial numbers.
- Integration điểm móc: order/delivery sử dụng batch_id/serial_number; inventory movement ghi kèm batch/serial.

3. Yêu cầu kỹ thuật
- Unique: batch per product; serial unique toàn hệ thống.
- Transactions: điều chỉnh quantity + movement phải atomic, lock hợp lý để tránh double-deduction.
- Expiry: expose list expiring (days ahead param) cho cảnh báo.
- Warranty: lưu warranty_expiry_date, trạng thái serial (available/sold/returned/damaged).

4. Testing (DevDatabaseTrait, main DB rollback)
- Unit: BatchServiceTest, SerialNumberServiceTest (create, adjust qty, expiring filter, uniqueness, sell/return flow, reserve guard).
- Integration: API create/list/adjust batch; create/reserve/sell/return serial; order item with batch/serial reduces availability.
- Coverage ≥70%, dùng patterns docs/testing/TESTING-PATTERNS.md.

5. Definition of Done
- Migrations + repo/service/controller hoàn chỉnh, tuân thủ clean architecture.
- Batch/serial gắn được vào order/delivery line; inventory movements ghi batch/serial.
- Unit + integration tests pass; cập nhật docs/erpnext/README.md nếu cần.
