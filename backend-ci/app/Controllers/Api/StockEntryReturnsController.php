<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Inventory\StockEntryReturnService;
use CodeIgniter\API\ResponseTrait;

/**
 * Stock entry return API.
 *
 * @agent-controller: StockEntryReturns
 * @agent-pattern: Thin controller
 */
class StockEntryReturnsController extends BaseController
{
    use ResponseTrait;

    protected StockEntryReturnService $service;

    public function __construct()
    {
        $this->service = service('stockEntryReturnService');
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->processReturn($payload)));
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
