<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Orders\OrderService;
use App\Services\Orders\OrderQueryService;
use CodeIgniter\API\ResponseTrait;

/** Orders API. @agent-controller: Orders @agent-pattern: Thin controller - routing only */
class OrdersController extends BaseController
{
    use ResponseTrait;

    protected OrderService $service;
    protected OrderQueryService $query;
    public function __construct()
    {
        $this->service = service('orderService');
        $this->query = service('orderQueryService');
    }

    /** List orders. @agent-use: GET /api/orders @agent-pattern: Thin list */
    public function index()
    {
        $filters = $this->request->getGet();
        return $this->wrap(fn () => $this->respond(['success' => true] + $this->query->list($filters)));
    }

    /** Order detail. @agent-use: GET /api/orders/{id} */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond(['success' => true, 'data' => $this->query->detail((int) $id)]));
    }

    /** Preview pricing. @agent-use: POST /api/orders/calculate-preview @agent-pattern: Delegate to service */
    public function calculatePreview()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->preview($payload)));
    }

    /** Create order with pricing applied. @agent-use: POST /api/orders @agent-pattern: Thin create */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
