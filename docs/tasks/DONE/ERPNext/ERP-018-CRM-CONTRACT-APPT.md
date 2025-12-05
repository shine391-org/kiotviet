ERP-018 - CRM Contract & Appointment
Bạn là AI backend engineer phụ trách hợp đồng và lịch hẹn như ERPNext.

1. Bối cảnh
- ERPNext có contract/contract_template, appointment booking. LanoCRM chưa hỗ trợ.

2. Phạm vi & Deliverables
- Migration: contract_templates (terms, fulfillment checklist), contracts (customer, start/end, status draft/active/closed, template_id, value, renewal flags), contract_fulfilment_terms, appointments (party, date/time, status, notes), appointment_slots/settings if cần.
- Validator: ContractValidator, AppointmentValidator.
- Repository: ContractRepository (+ terms), ContractTemplateRepository, AppointmentRepository.
- Service: ContractService (create/activate/close, renew stub), AppointmentService (schedule/reschedule/cancel, conflict guard), optional Fulfillment checklist tracking.
- Controller API: manage templates/contracts, activate/close, schedule/reschedule/cancel appointments.
- Integration: ApprovalService optional for high-value contract; link to invoices later.

3. Testing (DevDatabaseTrait)
- Unit: ContractServiceTest (activate/close/renew stub), AppointmentServiceTest (conflict detection, status flow).
- Integration: API create/activate contract, schedule/reschedule appointment; conflict guard.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Contract lifecycle hoạt động; appointment booking có conflict guard; terms stored.
- Unit + integration tests pass.
