ERP-010 - E-commerce Integration (Webhook Sync)
Bạn là AI backend engineer phụ trách tích hợp e-commerce cơ bản.

1. Bối cảnh
- Phase 3 nice-to-have: đồng bộ sản phẩm/đơn hàng với nền tảng e-commerce.

2. Phạm vi & Deliverables
- Service: EcommerceIntegrationService (webhook handlers, mapping product/order), WebhookAuthMiddleware.
- Controller API: Webhook endpoints (product sync, order sync), idempotency keys.
- Migration: webhook_events log nếu cần lưu trace.
- Validator: WebhookPayloadValidator.
- Integration: map product to internal product/variant, order to OrderService; handle retries/idempotent insert.

3. Testing (DevDatabaseTrait)
- Unit: EcommerceIntegrationServiceTest (mapping, idempotency, signature validation), WebhookAuthMiddlewareTest.
- Integration: webhook endpoints simulate product/order sync.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Webhook flow nhận và đồng bộ product/order tối thiểu; chống duplicate qua idempotency.
- Unit + integration tests pass.
