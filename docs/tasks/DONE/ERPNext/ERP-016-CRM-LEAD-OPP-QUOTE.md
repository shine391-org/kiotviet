ERP-016 - CRM Lead → Opportunity → Quotation
Bạn là AI backend engineer phụ trách pipeline CRM như ERPNext.

1. Bối cảnh
- ERPNext có lead, opportunity, quotation (sales). LanoCRM chưa đủ.

2. Phạm vi & Deliverables
- Migration: leads (status, source, contact info), opportunities (lead/prospect, items, probability, stage), opportunity_items, quotations (quote_number, customer/lead, items, validity, status), quotation_items.
- Validator: LeadValidator, OpportunityValidator, QuotationValidator.
- Repository: LeadRepository, OpportunityRepository, QuotationRepository (+ items).
- Service: LeadService (CRUD, convert to customer), OpportunityService (create/update, stage transitions, probability calc), QuotationService (generate quote from opportunity, pricing via PricingService, validity/expiry, approve/cancel), numbering helper for quotation.
- Controller API (thin, JWT): CRUD lead/opportunity/quotation, transitions (stage, cancel), convert lead → customer, create quotation from opportunity.
- Integration: PricingService for items; ApprovalService hook optional for high-value quotes; link to Delivery/Order later.

3. Testing (DevDatabaseTrait)
- Unit: LeadServiceTest, OpportunityServiceTest (stage transitions, probability), QuotationServiceTest (price calc, validity, cancel/approve).
- Integration: API create lead → opp → quote flow; expiry guard; approval hook if enabled.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Pipeline lead→opportunity→quotation hoạt động; giá tính qua PricingService; có thể convert lead thành customer.
- Unit + integration tests pass.
