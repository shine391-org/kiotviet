ERP-002 - Delivery Note Flow (Pick/Pack/Ship)
Bạn là AI backend engineer phụ trách xây Delivery Note chuẩn ERPNext cho LanoCRM.

1. Bối cảnh
- ERPNext có delivery_note với batch/serial, trạng thái draft → confirmed/packed/shipped/delivered/cancelled.
- LanoCRM thiếu toàn bộ fulfillment sau order.
- Phase 1 ưu tiên Inventory/Fulfillment.

2. Phạm vi & Deliverables
- Migration: delivery_notes (delivery_number unique, order_id, customer_id, branch_id, delivery_date, expected_delivery_date, status, shipping_address, tracking_number, carrier, notes, confirmed_by/at, delivered_by/at), delivery_note_items (order_item_id, product_id, variant_id, batch_id, serial_number, quantity, delivered_quantity, notes).
- Validator: DeliveryNoteValidator.
- Repository: DeliveryNoteRepository (create with items, find by order/number, update status, next number), DeliveryNoteItemRepository (CRUD items, update delivered qty).
- Service: DeliveryNoteService (createFromOrder, update items, confirm, mark shipped/delivered, cancel) + hooks vào inventory movement (trừ kho theo batch/serial, ghi ledger/bin nếu có).
- Controller API (thin, JWT): index/show/create/update, confirm, ship/deliver, cancel, create-from-order.
- Integration: Support partial deliveries; prevent over-delivery; enforce batch/serial validation if required by product.

3. Yêu cầu kỹ thuật
- Status machine rõ ràng, chặn nhảy bừa; cancel phải rollback movement/ledger.
- Numbering: delivery_number theo branch (function nextNumber).
- Transactions: confirm/deliver phải atomic (delivery note + items + movement/ledger + logs).
- Response chuẩn: success/message/data.

4. Testing (DevDatabaseTrait)
- Unit: DeliveryNoteServiceTest (create from order, confirm, deliver, partial delivery, cancel rollback).
- Integration: API create/confirm/deliver; ensure inventory deducted with batch/serial; over-delivery bị chặn.
- Coverage ≥70%, dùng patterns docs/testing/TESTING-PATTERNS.md.

5. Definition of Done
- Delivery note flow hoạt động từ order → delivery → movement/ledger.
- Batch/serial áp dụng khi product yêu cầu; không over-delivery.
- Unit + integration tests pass; docs/erpnext/README.md cập nhật nếu phát sinh tài liệu mới.

## Status
- [x] Migration + repos + service + controller
- [x] Unit tests
- [x] Integration tests
