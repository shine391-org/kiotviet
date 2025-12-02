ERP-019 - Support Ticket & Communication Log
Bạn là AI backend engineer phụ trách ticket/support/communication như ERPNext.

1. Bối cảnh
- ERPNext có Issue/Support Ticket, Communication log. LanoCRM chưa có.

2. Phạm vi & Deliverables
- Migration: support_tickets (subject, customer/lead, priority, status open/working/resolved/closed, assignment), ticket_comments/communications (type email/note, content, attachments ref), ticket_events (status log).
- Validator: SupportTicketValidator, CommunicationValidator.
- Repository: SupportTicketRepository, CommunicationRepository, TicketEventRepository.
- Service: SupportTicketService (CRUD, status transitions, assignment), CommunicationService (log communications linked to ticket/lead/opportunity), Notification hook stub.
- Controller API: CRUD ticket, add comment/communication, change status, assign.

3. Testing (DevDatabaseTrait)
- Unit: SupportTicketServiceTest (status flow, assignment), CommunicationServiceTest (log entry, validation).
- Integration: API create/update ticket, add communications, status transitions.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Support ticket + communication log hoạt động, trạng thái/assignment đúng.
- Unit + integration tests pass.
