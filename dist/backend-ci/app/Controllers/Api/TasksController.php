<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Projects\TaskService;
use CodeIgniter\API\ResponseTrait;

/**
 * Tasks API.
 *
 * @agent-controller: Tasks
 * @agent-pattern: Thin controller
 */
class TasksController extends BaseController
{
    use ResponseTrait;

    protected TaskService $service;

    public function __construct()
    {
        $this->service = service('taskService');
    }

    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->show((int) $id)));
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    public function updateStatus($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->updateStatus((int) $id, $payload)));
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
