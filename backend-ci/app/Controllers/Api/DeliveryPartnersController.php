<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\DeliveryPartners\DeliveryPartnerService;
use CodeIgniter\API\ResponseTrait;

/**
 * Delivery Partners API - for self-delivery partners (tự giao hàng)
 * @agent-controller: DeliveryPartners
 * @agent-pattern: Thin controller - routing only
 */
class DeliveryPartnersController extends BaseController
{
    use ResponseTrait;

    protected DeliveryPartnerService $service;

    public function __construct()
    {
        $this->service = service('deliveryPartnerService');
    }

    /** List delivery partners. @agent-use: GET /api/delivery-partners */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Get delivery partner detail. @agent-use: GET /api/delivery-partners/{id} */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    /** Create delivery partner. @agent-use: POST /api/delivery-partners */
    public function create()
    {
        return $this->wrap(fn () => $this->respondCreated($this->service->create($this->safeInput())));
    }

    /** Update delivery partner. @agent-use: PUT /api/delivery-partners/{id} */
    public function update($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $this->safeInput())));
    }

    /** Delete delivery partner. @agent-use: DELETE /api/delivery-partners/{id} */
    public function delete($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->delete((int) $id)));
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
            if (is_array($json)) {
                return $json;
            }
        } catch (\Throwable $e) {
        }
        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
