ERP-036 - Tuân thủ thuế địa phương (GST/VAT/Withholding nâng cao)
Bạn là AI backend engineer phụ trách mở rộng thuế theo gói khu vực như ERPNext.

1. Bối cảnh
- ERPNext có regional packs (GST/SAF-T...), withholding nâng cao. LanoCRM mới mức cơ bản.

2. Phạm vi & Deliverables
- Migration: regional_tax_rules (country, rule json), e_invoice_logs (nếu cần), tax_certificate_records.
- Validator: RegionalTaxValidator.
- Service: RegionalTaxService (áp dụng rule theo country, tính GST/VAT đặc thù, e-invoice stub), WithholdingAdvancedService (chứng từ khấu trừ), ExportComplianceService stub nếu cần.
- Controller API: cấu hình quốc gia, preview thuế theo rule, xuất chứng từ khấu trừ.
- Integration: Sales/Purchase Invoice dùng RegionalTaxService; PaymentEntry/Certificate lưu log; AccountingService ghi nhận.

3. Testing (DevDatabaseTrait)
- Unit: RegionalTaxServiceTest (rule apply), WithholdingAdvancedServiceTest (chứng từ), validation country.
- Integration: API preview/apply thuế theo country, tạo chứng từ khấu trừ.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Thuế địa phương áp dụng được qua rule, có chứng từ khấu trừ/e-invoice stub; gắn vào invoice/payment.
- Unit + integration tests pass.
