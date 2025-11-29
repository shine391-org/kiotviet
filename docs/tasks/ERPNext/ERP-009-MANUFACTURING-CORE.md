ERP-009 - Manufacturing Core (BOM & Work Order)
Bạn là AI backend engineer phụ trách tích hợp sản xuất cơ bản theo ERPNext.

1. Bối cảnh
- ERPNext có BOM và Work Order; LanoCRM chưa hỗ trợ sản xuất.
- Phase 3 (nice-to-have) cho make-to-order/make-to-stock.

2. Phạm vi & Deliverables
- Migration: bill_of_materials (product_id, version, is_active, qty, uom, cost), bom_items (component_product_id, qty, uom, scrap), work_orders (product_id, qty, bom_id, status draft/released/in_progress/completed/cancelled, planned_start/end, actual_start/end), work_order_operations optional.
- Validator: BOMValidator, WorkOrderValidator.
- Repository: BOMRepository, WorkOrderRepository.
- Service: BOMService (CRUD, validate components), WorkOrderService (create from sales order or manual, start/finish, consume components via ledger), integrate with StockLedgerService for consumption/production entries.
- Controller API (thin): manage BOM, work orders (create/start/finish/cancel).

3. Testing (DevDatabaseTrait)
- Unit: BOMServiceTest (validate components/quantities), WorkOrderServiceTest (start/finish posts ledger, status flow), ensure oversubscription prevented.
- Integration: API create/start/finish work order, ledger entries adjust inventory.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- BOM và Work Order vận hành cơ bản, ghi movement tiêu hao NVL và nhập thành phẩm.
- Không oversell linh kiện; trạng thái work order chính xác.
- Unit + integration tests pass.
