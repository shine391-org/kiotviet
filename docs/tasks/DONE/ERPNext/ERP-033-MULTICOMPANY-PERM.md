ERP-033 - Đa công ty & Phân quyền tài liệu
Bạn là AI backend engineer phụ trách đa công ty và quyền giống ERPNext.

1. Bối cảnh
- ERPNext hỗ trợ multi-company, quyền chia sẻ tài liệu. LanoCRM chưa có.

2. Phạm vi & Deliverables
- Migration: companies, company_permissions (user/role ↔ company), document_shares (entity_type/id, shared_with, perms), audit_logs/versioning (nếu chưa có).
- Validator: CompanyValidator, ShareValidator, PermissionValidator.
- Repository: CompanyRepository, ShareRepository, AuditLogRepository.
- Service: CompanyService (CRUD, default settings), PermissionService (check company scope, document-level ACL), SharingService (share/unshare), AuditService (log changes, version snapshot), middleware/hook to enforce company filter on queries.
- Controller API: manage companies, share/unshare document, list shares, audit log query.
- Integration: All services must respect company scope (filters), role-based checks; ApprovalService/Workflow should include company context.

3. Testing (DevDatabaseTrait)
- Unit: PermissionServiceTest (allow/deny by company/doc), SharingServiceTest, AuditServiceTest.
- Integration: API access blocked across companies; share grants access; audit log writes on update.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Đa công ty được enforce, chia sẻ tài liệu hoạt động, có audit/versioning tối thiểu.
- Unit + integration tests pass.
