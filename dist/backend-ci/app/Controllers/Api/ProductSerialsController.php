<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Products\ProductSerialNumberService;
use CodeIgniter\API\ResponseTrait;

/** Product serial numbers API. @agent-controller: Product serials @agent-pattern: Thin controller - routing only */
class ProductSerialsController extends BaseController
{
    use ResponseTrait;

    protected ProductSerialNumberService $service;

    public function __construct()
    {
        $this->service = service('productSerialNumberService');
    }

    /** List serial numbers. @agent-use: GET /api/product-serials */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Create serial number. @agent-use: POST /api/product-serials */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** Reserve serials. @agent-use: POST /api/product-serials/reserve */
    public function reserve()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->reserve($payload)));
    }

    /** Sell serials. @agent-use: POST /api/product-serials/sell */
    public function sell()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->sell($payload)));
    }

    /** Mark serials returned. @agent-use: POST /api/product-serials/return */
    public function markReturned()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->markReturned($payload)));
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
