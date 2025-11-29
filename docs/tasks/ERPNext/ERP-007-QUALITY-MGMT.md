ERP-007 - Quality Management (Inspection & Parameters)
Bạn là AI backend engineer phụ trách thêm quality inspection tương tự ERPNext.

1. Bối cảnh
- ERPNext có quality_inspection với parameters. LanoCRM chưa hỗ trợ.
- Phase 2 để đảm bảo chất lượng hàng nhập/xuất.

2. Phạm vi & Deliverables
- Migration: quality_parameters (name, uom, min/max/spec), quality_inspections (reference_type/id, status draft/submitted/approved/rejected, inspected_by, result), quality_inspection_items (parameter_id, value, pass/fail, notes).
- Validator: QualityParameterValidator, QualityInspectionValidator.
- Repository: QualityParameterRepository, QualityInspectionRepository.
- Service: QualityInspectionService (create/submit/approve/reject, evaluate pass/fail), hook điểm nhập kho/delivery nếu cần check.
- Controller API (thin): manage parameters, create inspection, submit/approve/reject.

3. Testing (DevDatabaseTrait)
- Unit: QualityInspectionServiceTest (evaluate pass/fail, status flow, missing parameter), QualityParameterRepositoryTest.
- Integration: API create/submit/approve inspection for receiving/delivery context.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Inspection flow chạy được, đánh giá tham số pass/fail, log trạng thái.
- Có thể gắn inspection vào inbound/outbound nếu module yêu cầu.
- Unit + integration tests pass.

## Status
- [x] Migrations + validators/repositories/services/controllers
- [x] Unit tests (QualityInspectionServiceTest, QualityParameterRepositoryTest)
- [x] Integration tests (QualityInspectionsApiTest)
