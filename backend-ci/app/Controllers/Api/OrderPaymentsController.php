<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Orders\OrderPaymentService;
use CodeIgniter\API\ResponseTrait;

class OrderPaymentsController extends BaseController
{
    use ResponseTrait;

    protected OrderPaymentService $service;

    public function __construct()
    {
        $this->service = new OrderPaymentService();
    }

    /**
     * POST /api/orders/{id}/payments
     */
    public function create($orderId)
    {
        try {
            $payload = $this->request->getJSON(true) ?? $this->request->getRawInput();
            $payload['order_id'] = (int) $orderId;
            $payload['created_by'] = $this->getCurrentUserId();
            $res = $this->service->addPayment($payload);
            return $this->respondCreated($res);
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    private function getCurrentUserId(): int
    {
        return 1; // TODO JWT
    }
}
