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
        $query = $this->request->getGet();
        return $this->wrap('index', fn () => $this->respond($this->service->list($query)), ['query' => $query]);
    }

    /** Expiring batches. @agent-use: GET /api/product-batches/expiring */
    public function expiring()
    {
        $query = $this->request->getGet();
        return $this->wrap('expiring', fn () => $this->respond($this->service->expiring($query)), ['query' => $query]);
    }

    /** Show batch detail. @agent-use: GET /api/product-batches/{id} */
    public function show($id)
    {
        $batchId = (int) $id;
        return $this->wrap('show', fn () => $this->respond($this->service->show($batchId)), ['id' => $batchId]);
    }

    /** Create batch. @agent-use: POST /api/product-batches */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap('create', fn () => $this->respondCreated($this->service->create($payload)), ['payload' => $payload]);
    }

    /** Update batch. @agent-use: PUT /api/product-batches/{id} */
    public function update($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $batchId = (int) $id;
        return $this->wrap('update', fn () => $this->respond($this->service->update($batchId, $payload)), ['id' => $batchId, 'payload' => $payload]);
    }

    /** Adjust quantity. @agent-use: POST /api/product-batches/{id}/adjust-quantity */
    public function adjust($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $batchId = (int) $id;
        return $this->wrap('adjust', fn () => $this->respond($this->service->adjustQuantity($batchId, $payload)), ['id' => $batchId, 'payload' => $payload]);
    }

    private function wrap(string $method, callable $action, array $context = [])
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            $this->logger->error(
                sprintf('ProductBatchesController::%s failed', $method),
                [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString(),
                    'context' => $context,
                ]
            );
            return $this->failServerError('Internal server error');
        }
    }
}
