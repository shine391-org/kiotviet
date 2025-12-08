<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Shipping\ShipmentService;
use CodeIgniter\API\ResponseTrait;

/** Shipments API - view of invoices with delivery info. @agent-controller: Shipments */
class ShipmentsController extends BaseController
{
    use ResponseTrait;

    protected ShipmentService $service;

    public function __construct()
    {
        $this->service = service('shipmentService');
    }

    /** List shipments. @agent-use: GET /api/shipments */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Get shipment detail. @agent-use: GET /api/shipments/{id} */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
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
