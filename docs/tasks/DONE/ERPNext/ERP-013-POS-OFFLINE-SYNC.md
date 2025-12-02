ERP-013 - POS Offline Queue & Sync
Bạn là AI backend engineer phụ trách khả năng offline/online cho POS.

1. Bối cảnh
- ERPNext POS hỗ trợ offline: lưu queue local, đồng bộ lên server khi online.
- LanoCRM chưa có offline queue.

2. Phạm vi & Deliverables
- Migration: pos_offline_queue (temp_id, payload json, status pending/synced/failed, user/device, created_at, synced_at, error), idempotency key mapping.
- Validator: POSOfflineValidator.
- Repository: POSOfflineQueueRepository.
- Service: POSOfflineService (enqueue, sync, conflict resolution, idempotent create/update order), POSIdempotencyService (reuse across webhooks if muốn).
- Controller API: endpoint nhận batch offline payload (orders/payments), returns per-item result; endpoint re-sync failed items.
- Integration: POS frontend có thể gửi khi online; server validates profile/shift/payment/batch/serial; detects duplicates via idempotency key.

3. Yêu cầu kỹ thuật
- Idempotent theo temp_id/device; không tạo trùng order.
- Conflict handling: nếu stock thiếu khi sync, trả lỗi cụ thể cho từng item.
- Transactions: mỗi order sync atomic; partial success across batch được báo cáo.

4. Testing (DevDatabaseTrait)
- Unit: POSOfflineServiceTest (enqueue, idempotent sync, conflict stock), POSOfflineQueueRepositoryTest.
- Integration: API sync batch offline (happy path, duplicate, stock fail), đảm bảo idempotency.
- Coverage ≥70% theo TESTING-PATTERNS.

5. Definition of Done
- Offline queue nhận và sync được, tránh tạo trùng, báo lỗi rõ ràng.
- POS orders tạo được từ offline payload với đủ validation (profile/shift/payment/batch/serial).
- Unit + integration tests pass.
