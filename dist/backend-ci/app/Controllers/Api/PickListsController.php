<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Inventory\PickPackService;
use CodeIgniter\API\ResponseTrait;

/**
 * Pick list API.
 *
 * @agent-controller: PickLists
 * @agent-pattern: Thin controller
 */
class PickListsController extends BaseController
{
    use ResponseTrait;

    protected PickPackService $service;

    public function __construct()
    {
        $this->service = service('pickPackService');
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createPickList($payload)));
    }

    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->showPickList((int) $id)));
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
