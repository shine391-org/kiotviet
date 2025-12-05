ERP-003 - Workflow/Approval Service (dùng chung)
Bạn là AI backend engineer phụ trách dựng engine phê duyệt dùng chung cho LanoCRM.

1. Bối cảnh
- Dự án: LanoCRM - ERPNext parity (Phase 1 ưu tiên Inventory/Fulfillment).
- Mục tiêu: Chuẩn hoá phê duyệt cho đơn hàng, stock reconciliation, delivery note... tránh viết thủ công từng module.
- Tham chiếu: AGENTS.md, docs/tasks/IMPLEMENTATION-PLAN-ERPNEXT-FEATURES.md, docs/analysis/ERPNEXT-LANOCRM-COMPARISON.md, docs/testing/TESTING-PATTERNS.md.

2. Phạm vi & Deliverables
- Migration: order_approval_rules, approvals (instances), approval_actions (log), với khóa ngoại order_id cho phiên bản đầu (thiết kế mở để gắn entity_type/entity_id sau).
- Validator: ApprovalRuleValidator (tạo/sửa rule), ApprovalRequestValidator (submit/approve/reject).
- Repository: ApprovalRuleRepository, ApprovalRepository (instance), ApprovalActionRepository (log).
- Service: ApprovalRuleService (CRUD + evaluate rule theo condition_type amount/customer/custom), ApprovalService (submit, approve, reject, escalate; bọc transaction), ApprovalHook (helper để module khác gọi).
- Controller API (thin, JWT): POST /api/approvals/submit, POST /api/approvals/{id}/approve, POST /api/approvals/{id}/reject, GET /api/approval-rules.
- Integration điểm móc: cung cấp helper để OrderService/StockReconciliationService/DeliveryNoteService có thể gọi `requireApprovalIfNeeded(...)` trước khi chuyển trạng thái, nhưng chưa bắt buộc bật ở tất cả flows trong task này (tạo sẵn contract + sample hook cho order confirm > threshold).

3. Yêu cầu kỹ thuật
- Clean architecture: Controller → Service → Repository, không business logic ở controller.
- Rule engine đơn giản (phase 1): condition_type = amount/customer/custom JSON; support multi-level bằng danh sách rule trả về nhiều approver (queue). Không cần BPM phức tạp.
- Transaction: submit/approve/reject phải atomic; lock row để tránh double approve.
- Audit: lưu action log, actor, timestamp; trả về message rõ ràng.
- Response chuẩn: success, message, data.

4. Testing (bắt buộc DevDatabaseTrait, main DB rollback)
- Unit: ApprovalRuleServiceTest, ApprovalServiceTest (submit, approve, reject, multi-level queue, invalid rule, double approve guard).
- Integration: API submit/approve/reject happy path + case vượt threshold yêu cầu phê duyệt; ensure transaction rollback bảo toàn dữ liệu khi fail.
- Coverage ≥70%, dùng patterns trong docs/testing/TESTING-PATTERNS.md.

5. Definition of Done
- Migrations, validators, repositories, services, controllers hoàn thiện, tuân thủ AGENTS.md.
- Hook mẫu cho Order: confirm đơn > ngưỡng phải tạo approval instance và chặn tiến trình nếu pending.
- Unit + integration tests pass (phpunit + phpunit.integration.xml nếu cần HTTP).
- Tài liệu cập nhật liên kết vào docs/erpnext/README.md nếu thêm file mới.

## Status
- [x] Migrations + services/controllers
- [x] Unit tests
- [x] Integration tests
