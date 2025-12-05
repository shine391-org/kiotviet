<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Inventory\StockReconciliationService;
use CodeIgniter\API\ResponseTrait;

/** Stock reconciliation API. @agent-controller: Stock reconciliations @agent-pattern: Thin controller - routing only */
class StockReconciliationsController extends BaseController
{
    use ResponseTrait;

    protected StockReconciliationService $service;

    public function __construct()
    {
        $this->service = service('stockReconciliationService');
    }

    /** List. @agent-use: GET /api/stock-reconciliations */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Detail. */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->show((int) $id)));
    }

    /** Create draft. */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** Submit draft. */
    public function submit($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->submit((int) $id)));
    }

    /** Approve reconciliation. */
    public function approve($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->approve((int) $id, $payload)));
    }

    /** Reject reconciliation. */
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
