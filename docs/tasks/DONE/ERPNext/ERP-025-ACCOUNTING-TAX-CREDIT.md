ERP-025 - Tax Templates, Withholding, Credit Control
Bạn là AI backend engineer phụ trách thuế/khấu trừ và credit limit như ERPNext.

1. Bối cảnh
- ERPNext có tax templates, withholding tax, credit limit & aging controls. LanoCRM chỉ có thuế cơ bản.

2. Phạm vi & Deliverables
- Migration: tax_templates (sales/purchase, inclusive/exclusive, charge_type), tax_template_items, withholding_rules, credit_limits (customer, limit_amount, on_hold flag), dunning_rules optional.
- Validator: TaxTemplateValidator, WithholdingValidator, CreditControlValidator.
- Repository: TaxTemplateRepository, WithholdingRuleRepository, CreditLimitRepository.
- Service: TaxTemplateService (CRUD/apply), WithholdingService (compute withholding lines), CreditControlService (check limit before order/invoice, place on hold, aging check stub), Aging helper uses GL.
- Controller API: manage tax templates/withholding rules/credit limits; endpoint check credit for customer.
- Integration: Sales/Purchase Invoice use TaxTemplateService/WithholdingService; OrderService uses CreditControlService before confirmation; ApprovalService tie-in for overrides.

3. Testing (DevDatabaseTrait)
- Unit: TaxTemplateServiceTest (apply inclusive/exclusive), WithholdingServiceTest, CreditControlServiceTest (limit check, hold), AgingHelperTest stub.
- Integration: Order/Invoice flow blocked if over limit unless approved; tax template applied correctly.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Thuế/khấu trừ áp dụng được; credit limit guard hoạt động với option override qua approval.
- Unit + integration tests pass.
