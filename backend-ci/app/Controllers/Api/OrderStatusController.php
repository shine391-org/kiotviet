<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Orders\OrderStatusService;
use CodeIgniter\API\ResponseTrait;

/** Order status API. @agent-controller: Order status @agent-pattern: Thin controller */
class OrderStatusController extends BaseController
{
    use ResponseTrait;

    protected OrderStatusService $service;

    public function __construct()
    {
        $this->service = service('orderStatusService');
    }

    /** Update status. @agent-use: PATCH /api/orders/{id}/status */
    public function update($id)
    {
        $input = $this->request->getJSON(true) ?? [];
        $status = $input['status'] ?? null;
        if (! $status) {
            return $this->failValidationErrors('status is required');
        }
        return $this->wrap(fn () => $this->respond($this->service->updateStatus((int) $id, $status, $this->userId(), $input['notes'] ?? null)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }

    private function userId(): ?int
    {
        return null; // placeholder, integrate auth later
    }
}
