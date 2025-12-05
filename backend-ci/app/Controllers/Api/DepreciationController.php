<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Assets\DepreciationService;
use CodeIgniter\API\ResponseTrait;

/**
 * Depreciation API.
 *
 * @agent-controller: Depreciation
 * @agent-pattern: Thin controller
 */
class DepreciationController extends BaseController
{
    use ResponseTrait;

    protected DepreciationService $service;

    public function __construct()
    {
        $this->service = service('depreciationService');
    }

    public function createSchedule()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createSchedule($payload)));
    }

    public function postLine($id, $period)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $data = $payload + ['schedule_id' => (int) $id, 'period_no' => (int) $period];
        return $this->wrap(fn () => $this->respond($this->service->postLine($data)));
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
