<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Assignments\AssignmentService;
use CodeIgniter\API\ResponseTrait;

/**
 * Assignment API.
 *
 * @agent-controller: Assignments
 * @agent-pattern: Thin controller
 */
class AssignmentsController extends BaseController
{
    use ResponseTrait;

    protected AssignmentService $service;

    public function __construct()
    {
        $this->service = service('assignmentService');
    }

    public function createRule()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createRule($payload)));
    }

    public function listRules()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    public function assign()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->assign($payload)));
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
