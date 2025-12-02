ERP-011 - Subscription Management
Bạn là AI backend engineer phụ trách subscription/recurring orders.

1. Bối cảnh
- Phase 3 nice-to-have: tự động tạo đơn định kỳ.

2. Phạm vi & Deliverables
- Migration: subscriptions (customer_id, template_id, plan_name, interval, next_run_at, status), subscription_cycles/logs.
- Validator: SubscriptionValidator.
- Repository: SubscriptionRepository, SubscriptionCycleRepository.
- Service: SubscriptionService (create/update, schedule runs, generate orders from template/pricing), background job hook stub.
- Controller API (thin): manage subscriptions, trigger run (admin), pause/resume/cancel.
- Integration: dùng OrderTemplateService + PricingService; idempotent per cycle.

3. Testing (DevDatabaseTrait)
- Unit: SubscriptionServiceTest (schedule, generate order once per cycle, pause/resume), idempotency guard.
- Integration: API create/pause/resume/cancel, trigger run produces order draft.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Subscriptions tạo đơn định kỳ đúng lịch, không trùng lặp, hỗ trợ pause/resume/cancel.
- Unit + integration tests pass.

## Status
- [x] Migrations + validators/repositories/services/controllers
- [x] Unit tests (SubscriptionServiceTest)
- [x] Integration tests (SubscriptionsApiTest)
