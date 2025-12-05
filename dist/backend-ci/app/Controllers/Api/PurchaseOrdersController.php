<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\PurchaseOrders\PurchaseOrderService;
use CodeIgniter\API\ResponseTrait;

/**
 * Purchase orders API.
 *
 * @agent-controller: PurchaseOrders
 * @agent-pattern: Thin controller - routing only
 */
class PurchaseOrdersController extends BaseController
{
    use ResponseTrait;

    protected PurchaseOrderService $service;

    public function __construct()
    {
        $this->service = service('purchaseOrderService');
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    public function submit($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->submit((int) $id)));
    }

    public function cancel($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->cancel((int) $id)));
    }

    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
