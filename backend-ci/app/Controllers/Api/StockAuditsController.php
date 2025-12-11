<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Inventory\StockAuditService;
use CodeIgniter\API\ResponseTrait;

/**
 * Stock audit API controller.
 * 
 * @agent-controller: Stock audits
 * @agent-pattern: Thin controller - routing only
 */
class StockAuditsController extends BaseController
{
    use ResponseTrait;

    protected StockAuditService $service;

    public function __construct()
    {
        $this->service = service('stockAuditService');
    }

    /** List stock audits. @agent-use: GET /api/inventory/stock-audits */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Show audit detail. @agent-use: GET /api/inventory/stock-audits/{code} */
    public function show($code)
    {
        return $this->wrap(fn () => $this->respond($this->service->show($code)));
    }

    /** Create draft audit. @agent-use: POST /api/inventory/stock-audits */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** Update draft audit. @agent-use: PUT /api/inventory/stock-audits/{id} */
    public function update($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $payload)));
    }

    /** Complete audit (mark as balanced). @agent-use: POST /api/inventory/stock-audits/{id}/complete */
    public function complete($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->complete((int) $id, $payload)));
    }

    /** Cancel audit. @agent-use: POST /api/inventory/stock-audits/{id}/cancel */
    public function cancel($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->cancel((int) $id, $payload)));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\App\Exceptions\NotFoundException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\RuntimeException $e) {
            log_message('error', 'StockAuditsController error: ' . $e->getMessage());
            return $this->failServerError('A server error occurred');
        } catch (\Throwable $e) {
            log_message('error', 'StockAuditsController error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError('An internal server error occurred');
        }
    }
}
