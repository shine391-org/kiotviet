<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Loyalty\LoyaltyService;
use CodeIgniter\API\ResponseTrait;

class LoyaltyController extends BaseController
{
    use ResponseTrait;

    protected LoyaltyService $service;

    public function __construct()
    {
        $this->service = service('loyaltyService');
    }

    /** Get customer wallet. GET /api/loyalty/wallet/{customerId} */
    public function wallet($customerId)
    {
        return $this->wrap(fn () => $this->respond($this->service->getWallet((int) $customerId)));
    }

    /** Calculate earn points. POST /api/loyalty/calculate-earn */
    public function calculateEarn()
    {
        $input = $this->safeInput();
        return $this->wrap(fn () => $this->respond(
            $this->service->calculateEarnPoints((int) $input['customer_id'], (float) $input['order_total'])
        ));
    }

    /** Preview redeem. POST /api/loyalty/redeem-preview */
    public function redeemPreview()
    {
        $input = $this->safeInput();
        return $this->wrap(fn () => $this->respond(
            $this->service->redeemPreview((int) $input['customer_id'], (float) $input['points'])
        ));
    }

    /** Earn points after order. POST /api/loyalty/earn */
    public function earn()
    {
        $input = $this->safeInput();
        return $this->wrap(fn () => $this->respond(
            $this->service->earnPoints((int) $input['customer_id'], (float) $input['order_total'], $input['order_id'] ?? null)
        ));
    }

    /** Redeem points. POST /api/loyalty/redeem */
    public function redeem()
    {
        $input = $this->safeInput();
        return $this->wrap(fn () => $this->respond(
            $this->service->redeemPoints((int) $input['customer_id'], (float) $input['points'], $input['order_id'] ?? null)
        ));
    }

    /** Get transaction history. GET /api/loyalty/transactions/{customerId} */
    public function transactions($customerId)
    {
        return $this->wrap(fn () => $this->respond($this->service->getTransactions((int) $customerId)));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) return $json;
        } catch (\Throwable $e) {}
        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
