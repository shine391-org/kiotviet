<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Shipping\ShippingService;
use CodeIgniter\API\ResponseTrait;

class ShippingController extends BaseController
{
    use ResponseTrait;

    protected ShippingService $service;

    public function __construct()
    {
        $this->service = service('shippingService');
    }

    /** Calculate shipping fee. POST /api/shipping/calculate */
    public function calculate()
    {
        return $this->wrap(fn () => $this->respond($this->service->calculateFee($this->safeInput())));
    }

    /** List shipping zones. GET /api/shipping/zones */
    public function zones()
    {
        return $this->wrap(fn () => $this->respond($this->service->listZones($this->request->getGet())));
    }

    /** Create zone. POST /api/shipping/zones */
    public function createZone()
    {
        return $this->wrap(fn () => $this->respondCreated($this->service->createZone($this->safeInput())));
    }

    /** Update zone. PUT /api/shipping/zones/{id} */
    public function updateZone($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->updateZone((int) $id, $this->safeInput())));
    }

    /** Delete zone. DELETE /api/shipping/zones/{id} */
    public function deleteZone($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->deleteZone((int) $id)));
    }

    /** Get rates for zone. GET /api/shipping/zones/{id}/rates */
    public function rates($zoneId)
    {
        return $this->wrap(fn () => $this->respond($this->service->getRates((int) $zoneId)));
    }

    /** Create rate. POST /api/shipping/rates */
    public function createRate()
    {
        return $this->wrap(fn () => $this->respondCreated($this->service->createRate($this->safeInput())));
    }

    /** Update rate. PUT /api/shipping/rates/{id} */
    public function updateRate($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->updateRate((int) $id, $this->safeInput())));
    }

    /** Delete rate. DELETE /api/shipping/rates/{id} */
    public function deleteRate($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->deleteRate((int) $id)));
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

    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) return $json;
        } catch (\Throwable $e) {}
        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
