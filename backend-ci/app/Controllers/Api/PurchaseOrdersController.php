<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\PurchaseOrders\PurchaseOrderStatusService;
use CodeIgniter\API\ResponseTrait;

class PurchaseOrdersController extends BaseController
{
    use ResponseTrait;

    protected PurchaseOrderStatusService $statusService;

    public function __construct()
    {
        $this->statusService = new PurchaseOrderStatusService();
    }

    /**
     * POST /api/purchase-orders/{id}/receive
     */
    public function receive($id)
    {
        try {
            $userId = $this->getCurrentUserId();
            $result = $this->statusService->markReceived((int) $id, $userId);
            return $this->respond($result);
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    private function getCurrentUserId(): int
    {
        return 1; // TODO: JWT
    }
}
