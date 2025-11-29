<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Manufacturing\WorkOrderService;
use CodeIgniter\API\ResponseTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Work orders API controller.
 *
 * @agent-controller: Work orders
 * @agent-pattern: Thin controller - routing only
 */
class WorkOrdersController extends BaseController
{
    use ResponseTrait;

    protected WorkOrderService $service;

    public function __construct()
    {
        $this->service = service('workOrderService');
    }

    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->show((int) $id)));
    }

    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($data)));
    }

    public function release($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->release((int) $id)));
    }

    public function start($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->start((int) $id)));
    }

    public function complete($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->complete((int) $id)));
    }

    public function cancel($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->cancel((int) $id)));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
