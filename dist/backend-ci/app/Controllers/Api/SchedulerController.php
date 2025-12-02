<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Jobs\SchedulerService;
use CodeIgniter\API\ResponseTrait;

/**
 * Scheduler admin API.
 *
 * @agent-controller: Scheduler
 * @agent-pattern: Thin controller
 */
class SchedulerController extends BaseController
{
    use ResponseTrait;

    protected SchedulerService $service;

    public function __construct()
    {
        $this->service = service('schedulerService');
    }

    public function createRule()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createRule($payload)));
    }

    public function listRules()
    {
        return $this->wrap(fn () => $this->respond($this->service->listRules($this->request->getGet())));
    }

    public function tick()
    {
        return $this->wrap(fn () => $this->respond($this->service->tick()));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
