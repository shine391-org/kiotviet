<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Approvals\ApprovalService;
use CodeIgniter\API\ResponseTrait;

/** Approvals API. @agent-controller: Approvals @agent-pattern: Thin controller - routing only */
class ApprovalsController extends BaseController
{
    use ResponseTrait;

    protected ApprovalService $service;

    public function __construct()
    {
        $this->service = service('approvalService');
    }

    /** Submit order approval. @agent-use: POST /api/approvals/submit */
    public function submit()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $order = $this->service->loadOrderWithItems((int) ($payload['order_id'] ?? 0));
        return $this->wrap(fn () => $this->respond($this->service->submitOrder($payload, $order)));
    }

    /** Approve. @agent-use: POST /api/approvals/{id}/approve */
    public function approve($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->approve((int) $id, $payload)));
    }

    /** Reject. @agent-use: POST /api/approvals/{id}/reject */
    public function reject($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->reject((int) $id, $payload)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }

}
