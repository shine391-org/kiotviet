ERP-004 - Stock Ledger & Reconciliation
Bạn là AI backend engineer phụ trách sổ kho và kiểm kê theo chuẩn ERPNext.

1. Bối cảnh
- LanoCRM chưa có stock ledger/bin ổn định; ERPNext dùng stock_ledger_entry + bin để tracking tồn theo warehouse.
- Cần sổ kho chuẩn để batch/delivery/approval hoạt động chính xác.

2. Phạm vi & Deliverables
- Migration: stock_ledgers (product_id, variant_id?, branch_id/warehouse_id, movement_date, reference_type/id, qty_delta, batch_id, serial_number, unit_cost, total_cost), stock_bins (product_id, branch_id, batch_id nullable, on_hand_qty, reserved_qty), stock_reconciliations + stock_reconciliation_items (theo plan), indexes cho tra cứu nhanh.
- Validator: StockLedgerValidator, StockReconciliationValidator.
- Repository: StockLedgerRepository (ghi/đọc ledger, query by product/branch/batch, aggregate), StockBinRepository (read/update with locking), StockReconciliationRepository.
- Service: StockLedgerService (ghi movement + update bin atomically), StockReconciliationService (draft→submitted→approved, tính variance, post ledger), helper cho DeliveryNoteService/OrderService để ghi movement.
- Controller API (thin): StockReconciliationsController (create, submit, approve/reject, list, detail); optional ledger query endpoint (read-only) nếu cần.
- Integration: Gắn ledger update vào delivery (ship/deliver), batch/serial adjust, reconciliation approval.

3. Yêu cầu kỹ thuật
- Transaction bắt buộc cho ledger+bin update; dùng row-level lock để tránh oversell.
- Đảm bảo idempotency: không ghi trùng movement cho cùng reference_type/id + seq.
- Bin phải hỗ trợ batch_id (nếu product dùng batch), nếu không thì null.
- Valuation: lưu unit_cost/total_cost dù chưa tính COGS đầy đủ (phase 1, có thể dùng average đơn giản).

4. Testing (DevDatabaseTrait)
- Unit: StockLedgerServiceTest (ghi movement tăng/giảm, batch vs non-batch, concurrent guard), StockReconciliationServiceTest (variance calc, approve post ledger, reject rollback), BinRepositoryTest (lock/update).
- Integration: API reconciliation create/approve, ledger reflects delivery note deduction, prevents duplicate movement.
- Coverage ≥70%, theo docs/testing/TESTING-PATTERNS.md.

5. Definition of Done
- Ledger + bin hoạt động, không oversell, hỗ trợ batch/serial hooks.
- Reconciliation flow từ draft→approved ghi đúng variance vào ledger/bin.
- Unit + integration tests pass; cập nhật docs/erpnext/README.md nếu thêm tài liệu.
