ERP-006 - Reorder Level Planning
Bạn là AI backend engineer phụ trách gợi ý mua hàng dựa trên tồn kho/reorder level.

1. Bối cảnh
- ERPNext có reorder level, purchase suggestions. LanoCRM chưa có.
- Phase 2 ưu tiên planning để giảm hết hàng.

2. Phạm vi & Deliverables
- Migration: reorder_levels (product/variant, branch, min_level, max_level, safety_stock), purchase_suggestions (generated_from date, status, suggested_qty, reason), indexes.
- Validator: ReorderLevelValidator, PurchaseSuggestionValidator.
- Repository: ReorderLevelRepository (CRUD, query thiếu hụt), PurchaseSuggestionRepository.
- Service: ReorderPlanningService (tính tồn khả dụng từ bin/ledger, so sánh reorder_level, tạo suggestions; merge per supplier optional), PurchaseSuggestionService (acknowledge/convert to PO stub if available).
- Controller API (thin): manage reorder levels, generate suggestions, list suggestions.
- Integration: sử dụng bin/ledger (ERP-004) để tính on_hand và reserved; cho phép filter theo branch/supplier.

3. Testing (DevDatabaseTrait)
- Unit: ReorderPlanningServiceTest (tính thiếu hụt, safety stock, multi-branch), ReorderLevelRepositoryTest.
- Integration: API generate suggestions, verify quantities và trạng thái.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Reorder suggestions tạo đúng theo tồn khả dụng và ngưỡng; không trùng lặp khi chạy lại cùng ngày.
- CRUD reorder level hoạt động; integration với bin/ledger.
- Unit + integration tests pass.
