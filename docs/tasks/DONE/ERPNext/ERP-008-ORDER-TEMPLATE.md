ERP-008 - Order Templates (Recurring/Quick Order)
Bạn là AI backend engineer phụ trách order template giống ERPNext.

1. Bối cảnh
- ERPNext có order template cho đặt hàng lặp lại/nhanh. LanoCRM chưa có.
- Phase 2 để tăng tốc bán hàng.

2. Phạm vi & Deliverables
- Migration: order_templates (name, customer_id optional, frequency optional, is_active), order_template_items (product_id, variant_id, quantity, price, notes), order_subscriptions (template_id, next_run_at, status) nếu cần recurring.
- Validator: OrderTemplateValidator, OrderSubscriptionValidator.
- Repository: OrderTemplateRepository, OrderTemplateItemRepository, OrderSubscriptionRepository.
- Service: OrderTemplateService (CRUD template/items, apply template to create order draft), OrderSubscriptionService (schedule/instantiate orders) optional.
- Controller API (thin): manage templates, apply template, manage subscriptions (if included).
- Integration: hook vào OrderService để tạo draft từ template, giữ pricing logic qua PricingService.

3. Testing (DevDatabaseTrait)
- Unit: OrderTemplateServiceTest (create/update template, apply template to order draft, inactive template guard), OrderSubscriptionServiceTest (if implemented).
- Integration: API create/apply template; ensure pricing and items copy đúng.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Template có thể lưu và áp dụng tạo order draft chuẩn, không phá workflow hiện tại.
- Nếu triển khai subscription: tạo đơn định kỳ đúng lịch, tránh trùng lặp.
- Unit + integration tests pass.

## Status
- [x] Migrations + validators/repositories/services/controllers
- [x] Unit tests (OrderTemplateServiceTest covering subscriptions)
- [x] Integration tests (OrderTemplatesApiTest)
