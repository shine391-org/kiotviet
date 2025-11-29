<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Products\ProductBatchService;
use CodeIgniter\API\ResponseTrait;

/** Product batches API. @agent-controller: Product batches @agent-pattern: Thin controller - routing only */
class ProductBatchesController extends BaseController
{
    use ResponseTrait;

    protected ProductBatchService $service;

    public function __construct()
    {
        $this->service = service('productBatchService');
    }

    /** List batches. @agent-use: GET /api/product-batches */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Expiring batches. @agent-use: GET /api/product-batches/expiring */
    public function expiring()
    {
        return $this->wrap(fn () => $this->respond($this->service->expiring($this->request->getGet())));
    }

    /** Show batch detail. @agent-use: GET /api/product-batches/{id} */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->show((int) $id)));
    }

    /** Create batch. @agent-use: POST /api/product-batches */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** Update batch. @agent-use: PUT /api/product-batches/{id} */
    public function update($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $payload)));
    }

    /** Adjust quantity. @agent-use: POST /api/product-batches/{id}/adjust-quantity */
    public function adjust($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->adjustQuantity((int) $id, $payload)));
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
            return $this->failServerError($e->getMessage());
        }
    }
}
