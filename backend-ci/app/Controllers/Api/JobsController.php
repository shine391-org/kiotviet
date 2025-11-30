<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Jobs\SchedulerService;
use App\Services\Jobs\JobRunnerService;
use CodeIgniter\API\ResponseTrait;

/**
 * Jobs admin API.
 *
 * @agent-controller: Jobs
 * @agent-pattern: Thin controller
 */
class JobsController extends BaseController
{
    use ResponseTrait;

    protected SchedulerService $scheduler;
    protected JobRunnerService $runner;

    public function __construct()
    {
        $this->scheduler = service('schedulerService');
        $this->runner = service('jobRunnerService');
    }

    public function enqueue()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated(['success' => true, 'data' => $this->scheduler->enqueueHandler($payload['name'] ?? '', $payload['payload'] ?? [], $payload['next_run_at'] ?? null)]));
    }

    public function runNext()
    {
        return $this->wrap(fn () => $this->respond(['success' => true, 'data' => $this->runner->runNext()]));
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
