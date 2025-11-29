ERP-005 - Advanced Pricing (Customer/Project-specific, History)
Bạn là AI backend engineer phụ trách mở rộng hệ thống giá theo chuẩn ERPNext.

1. Bối cảnh
- ERPNext có pricing rules, customer/project-specific pricing, lịch sử giá. LanoCRM mới chỉ có price list cơ bản.
- Phase 2 ưu tiên pricing/control để hỗ trợ sales nâng cao.
- Tham chiếu: AGENTS.md, docs/tasks/IMPLEMENTATION-PLAN-ERPNEXT-FEATURES.md, docs/analysis/ERPNEXT-LANOCRM-COMPARISON.md.

2. Phạm vi & Deliverables
- Migration: customer_price_lists, project_price_lists, pricing_rules (condition_type amount/customer/product/date range), price_history log.
- Validator: PricingRuleValidator, CustomerPriceListValidator.
- Repository: PricingRuleRepository, CustomerPriceListRepository, PriceHistoryRepository.
- Service: PricingRuleService (CRUD + evaluate), PricingService (tính giá cho product/variant với ưu tiên customer→project→list), ghi history khi giá thay đổi.
- Controller API (thin): endpoints quản lý price lists/rules, endpoint tính giá preview.
- Integration: Order/Quote dùng PricingService để lấy giá; trả về lý do chọn rule.

3. Testing (DevDatabaseTrait)
- Unit: PricingRuleServiceTest, PricingServiceTest (ưu tiên rule, date range, customer override, history ghi nhận).
- Integration: API preview giá, áp dụng giá trong order line.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Pricing engine áp dụng được customer/project/date rules, fallback price list.
- Order/Quote tích hợp PricingService; history lưu lại.
- Unit + integration tests pass.
