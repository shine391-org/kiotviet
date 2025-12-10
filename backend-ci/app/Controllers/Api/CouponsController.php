<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Coupons\CouponService;
use CodeIgniter\API\ResponseTrait;

class CouponsController extends BaseController
{
    use ResponseTrait;

    protected CouponService $service;

    public function __construct()
    {
        $this->service = service('couponService');
    }

    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    public function create()
    {
        return $this->wrap(fn () => $this->respondCreated($this->service->create($this->safeInput())));
    }

    public function update($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $this->safeInput())));
    }

    public function delete($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->delete((int) $id)));
    }

    /** Apply coupon for POS. POST /api/coupons/apply */
    public function apply()
    {
        $input = $this->safeInput();
        $code = $input['code'] ?? '';
        $customerId = $input['customer_id'] ?? null;

        // Validate order_total is provided and numeric
        if (!isset($input['order_total']) || !is_numeric($input['order_total'])) {
            return $this->failValidationErrors('order_total is required and must be numeric');
        }
        $orderTotal = (float) $input['order_total'];

        return $this->wrap(fn () => $this->respond($this->service->apply($code, $orderTotal, $customerId)));
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
