ERP-028 - Stock Entry nâng cao (Issue/Receipt/Transfer/Returns)
Bạn là AI backend engineer phụ trách Stock Entry đa loại như ERPNext.

1. Bối cảnh
- ERPNext dùng Stock Entry cho issue/receipt/transfer, return, pick/pack, warehouse transfer. LanoCRM mới có movement cơ bản.

2. Phạm vi & Deliverables
- Migration: stock_entries (entry_number, type issue/receipt/transfer/return, source/destination warehouse, status, reference), stock_entry_items (product/variant, qty, batch/serial, s_warehouse, t_warehouse), pick_lists, packing_slips nếu tách bảng; return reason.
- Validator: StockEntryValidator, PickListValidator, PackingSlipValidator.
- Repository: StockEntryRepository (+ items), PickListRepository, PackingSlipRepository.
- Service: StockEntryService (create/submit/cancel cho từng type, post ledger/bin), PickPackService (tạo pick list, packing slip), ReturnService (process return stock entry), numbering helper.
- Controller API: tạo/submit/cancel stock entry; tạo pick/pack; return entry.
- Integration: DeliveryNote/PO/WO có thể sinh stock entry; batch/serial validation; ApprovalService optional.

3. Testing (DevDatabaseTrait)
- Unit: StockEntryServiceTest (issue/receipt/transfer/return), PickPackServiceTest, ReturnServiceTest, đảm bảo ledger/bin cập nhật đúng.
- Integration: API tạo transfer giữa kho, return deduct/add đúng; pick/pack flow.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Stock Entry đa loại hoạt động, không oversell, hỗ trợ batch/serial, pick/pack/return.
- Unit + integration tests pass.
