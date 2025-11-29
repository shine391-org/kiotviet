ERP-032 - Cổng khách hàng & Thông báo/Assignment
Bạn là AI backend engineer phụ trách customer portal và notification/assignment như ERPNext.

1. Bối cảnh
- ERPNext có customer portal, knowledge base, assignment rules, notification engine. LanoCRM chưa có.

2. Phạm vi & Deliverables
- Migration: portal_users (link customer), portal_access_tokens, knowledge_base_articles/categories, notifications (templates/rules), assignment_rules, assignment_logs.
- Validator: PortalValidator, NotificationValidator, AssignmentValidator.
- Repository: PortalUserRepository, KnowledgeBaseRepository, NotificationRuleRepository, AssignmentRepository.
- Service: PortalService (login/token, access control), KnowledgeBaseService (CRUD), NotificationService (trigger based on events, channel-agnostic stub), AssignmentService (assign based on rules/round robin), ties to Support/CRM modules.
- Controller API: portal auth endpoints, knowledge base read, assignment/notification admin endpoints.
- Integration: Hooks từ ticket/opportunity/order để trigger notification/assignment; use ApprovalService/Email provider abstraction.

3. Testing (DevDatabaseTrait)
- Unit: PortalServiceTest (token/access), NotificationServiceTest (rule match), AssignmentServiceTest (round robin), KnowledgeBaseServiceTest.
- Integration: API portal login + view KB, trigger notification/assignment on ticket/opportunity creation (stub delivery).
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Portal truy cập khách hàng, KB sẵn; assignment/notification rules chạy được (dù gửi stub), tích hợp ticket/CRM.
- Unit + integration tests pass.
