ERP-026 - Multi-currency & Aging Reports
Bạn là AI backend engineer phụ trách đa tiền tệ và báo cáo công nợ như ERPNext.

1. Bối cảnh
- ERPNext hỗ trợ đa tiền tệ, tỷ giá, và báo cáo Aging (AR/AP). LanoCRM chưa có.

2. Phạm vi & Deliverables
- Migration: exchange_rates (currency, rate, valid_from), currency_settings if needed.
- Validator: ExchangeRateValidator.
- Repository: ExchangeRateRepository.
- Service: CurrencyService (convert amounts, pick rate by date), AgingService (AR/AP aging using GL entries grouped by party, bucket by days, multi-currency handling), hook to CreditControl.
- Controller API: manage exchange rates, get aging report (filters date/party/branch/currency).
- Integration: Invoice/Payment services use CurrencyService for conversions; reports read from GL.

3. Testing (DevDatabaseTrait)
- Unit: CurrencyServiceTest (rate selection, conversion), AgingServiceTest (bucket totals, multi-currency), edge cases missing rate.
- Integration: API aging report with sample GL data.
- Coverage ≥70% theo TESTING-PATTERNS.

4. Definition of Done
- Đa tiền tệ hỗ trợ đầy đủ trong tính toán; báo cáo aging xuất đúng; kết nối credit control.
- Unit + integration tests pass.
