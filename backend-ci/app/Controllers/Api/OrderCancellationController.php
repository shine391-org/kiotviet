<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Orders\OrderCancellationService;
use CodeIgniter\API\ResponseTrait;

/** Order cancellation API. @agent-controller: Order cancel @agent-pattern: Thin controller */
class OrderCancellationController extends BaseController
{
    use ResponseTrait;

    protected OrderCancellationService $service;

    public function __construct()
    {
        $this->service = service('orderCancellationService');
    }

    /** Cancel order. @agent-use: POST /api/orders/{id}/cancel */
    public function cancel($id)
    {
        $input = $this->request->getJSON(true) ?? [];
        $reason = $input['reason'] ?? null;
        return $this->wrap(fn () => $this->respond($this->service->cancel((int) $id, $reason, $this->userId())));
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
        return null; // integrate auth later
    }
}
