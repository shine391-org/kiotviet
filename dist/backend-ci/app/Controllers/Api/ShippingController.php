<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Shipping\ShippingCODSettlementService;
use CodeIgniter\API\ResponseTrait;

/**
 * Shipping endpoints.
 *
 * @agent-controller: Shipping
 * @agent-pattern: Thin controller
 */
class ShippingController extends BaseController
{
    use ResponseTrait;

    protected ShippingCODSettlementService $settlement;

    public function __construct()
    {
        $this->settlement = new ShippingCODSettlementService();
    }

    /**
     * POST /api/shipping/settlement
     */
    public function settleCOD()
    {
        try {
            $data = $this->request->getJSON(true) ?? $this->request->getRawInput();
            $partnerId = (int) ($data['shipping_partner_id'] ?? 0);
            $orderIds = $data['order_ids'] ?? [];
            $shippingFee = (float) ($data['total_shipping_fee'] ?? 0);
            $userId = $this->getCurrentUserId();

            $result = $this->settlement->settleCOD($partnerId, (array) $orderIds, $shippingFee, $userId);
            return $this->respond($result);
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    private function getCurrentUserId(): int
    {
        return 1; // TODO: parse JWT
    }
}
