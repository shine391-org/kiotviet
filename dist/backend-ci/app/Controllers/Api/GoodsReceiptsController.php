<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Inventory\GoodsReceiptService;
use CodeIgniter\API\ResponseTrait;

/**
 * Goods receipts API.
 *
 * @agent-controller: GoodsReceipts
 * @agent-pattern: Thin controller - routing only
 */
class GoodsReceiptsController extends BaseController
{
    use ResponseTrait;

    protected GoodsReceiptService $service;

    public function __construct()
    {
        $this->service = service('goodsReceiptService');
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
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
