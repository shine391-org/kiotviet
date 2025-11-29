ERP-014 - POS Loyalty & Coupon Discounts
Bạn là AI backend engineer phụ trách loyalty/coupon giống ERPNext POS.

1. Bối cảnh
- ERPNext POS hỗ trợ loyalty points, coupon/discount rules. LanoCRM chưa có.

2. Phạm vi & Deliverables
- Migration: loyalty_programs (customer_group?, earn_rate, redeem_rate, expiry_days), loyalty_wallets (customer_id, points_balance, last_earned_at), loyalty_transactions (order_id, points_delta, reason), coupons (code, discount_type/value, min_amount, expiry, usage_limit, status), coupon_usages.
- Validator: LoyaltyProgramValidator, CouponValidator.
- Repository: LoyaltyRepository (wallet/transactions), CouponRepository.
- Service: LoyaltyService (earn/redeem, compute points, guard negative), CouponService (validate/apply coupon, mark usage), integrate with PricingService for discount application.
- Controller API: apply coupon/loyalty preview, redeem points on POS checkout, admin CRUD for programs/coupons.
- Integration: POS checkout supports coupon code + points redemption; updates wallet and coupon usage atomically with order/payment.

3. Yêu cầu kỹ thuật
- Prevent double redeem; enforce expiry/usage limits; handle partial redemption.
- Transactions: order + loyalty_tx + coupon_usage atomic.

4. Testing (DevDatabaseTrait)
- Unit: LoyaltyServiceTest (earn/redeem, expiry, insufficient points), CouponServiceTest (valid/invalid, usage limit, expiry).
- Integration: POS checkout with coupon + redeem points; idempotency guard.
- Coverage ≥70% theo TESTING-PATTERNS.

5. Definition of Done
- Loyalty points và coupon áp dụng được trong POS checkout, cập nhật wallet/usage đúng.
- Unit + integration tests pass.
