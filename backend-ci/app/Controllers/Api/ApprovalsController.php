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
        $order = $this->loadOrder((int) ($payload['order_id'] ?? 0));
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

    private function loadOrder(int $orderId): array
    {
        if ($orderId <= 0) {
            return [];
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $order = $db->table('orders')->where('id', $orderId)->get()->getRowArray() ?? [];
        $order['items'] = $db->table('order_items')->where('order_id', $orderId)->get()->getResultArray();
        return $order;
    }
}
