<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Inventory\StockDisposalService;
use CodeIgniter\API\ResponseTrait;

/**
 * Stock disposal API controller.
 * 
 * @agent-controller: Stock disposals
 * @agent-pattern: Thin controller - routing only
 */
class StockDisposalsController extends BaseController
{
    use ResponseTrait;

    protected StockDisposalService $service;

    public function __construct()
    {
        $this->service = new StockDisposalService();
    }

    /** List stock disposals. @agent-use: GET /api/inventory/disposals */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Show disposal detail. @agent-use: GET /api/inventory/disposals/{code} */
    public function show($code)
    {
        return $this->wrap(fn () => $this->respond($this->service->show($code)));
    }

    /** Create disposal. @agent-use: POST /api/inventory/disposals */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** Update disposal. @agent-use: PUT /api/inventory/disposals/{id} */
    public function update($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $payload)));
    }

    /** Complete disposal. @agent-use: POST /api/inventory/disposals/{id}/complete */
    public function complete($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->complete((int) $id, $payload)));
    }

    /** Cancel disposal. @agent-use: POST /api/inventory/disposals/{id}/cancel */
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
        } catch (\RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', 'StockDisposalsController error: ' . $e->getMessage());
            return $this->failServerError($e->getMessage());
        }
    }
}
