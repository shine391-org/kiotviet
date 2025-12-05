<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Coupons\CouponService;
use App\Services\Loyalty\LoyaltyService;
use CodeIgniter\API\ResponseTrait;

/**
 * @agent-controller: POS loyalty & coupons
 * @agent-pattern: Thin controller - routing only
 */
class POSLoyaltyController extends BaseController
{
    use ResponseTrait;

    protected CouponService $coupons;
    protected LoyaltyService $loyalty;

    public function __construct()
    {
        $this->coupons = service('couponService');
        $this->loyalty = service('loyaltyService');
    }

    /** Apply coupon preview. @agent-use: POST /api/pos/coupons/apply */
    public function applyCoupon()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $code = $payload['code'] ?? '';
        $amount = (float) ($payload['amount'] ?? 0);
        return $this->wrap(fn () => $this->respond($this->coupons->apply($code, $amount)));
    }

    /** Redeem points preview. @agent-use: POST /api/pos/loyalty/redeem-preview */
    public function redeemPreview()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $customerId = isset($payload['customer_id']) ? (int) $payload['customer_id'] : 0;
        $points = isset($payload['points']) ? (int) $payload['points'] : 0;
        if ($customerId <= 0) {
            return $this->failValidationErrors('customer_id is required');
        }
        return $this->wrap(fn () => $this->respond([
            'success' => true,
            'data' => $this->loyalty->previewRedeem($customerId, $points),
        ]));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
