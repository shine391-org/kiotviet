<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Returns\ReturnService;
use CodeIgniter\API\ResponseTrait;

/** Returns API. @agent-controller: Returns @agent-pattern: Thin controller */
class ReturnsController extends BaseController
{
    use ResponseTrait;

    protected ReturnService $service;

    public function __construct()
    {
        $this->service = service('returnService');
    }

    /** List returns. @agent-use: GET /api/returns */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Get return. @agent-use: GET /api/returns/{id} */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    /** Create return. @agent-use: POST /api/returns */
    public function create()
    {
        return $this->wrap(fn () => $this->respondCreated($this->service->create($this->safeInput())));
    }

    /** Approve return. @agent-use: PATCH /api/returns/{id}/approve */
    public function approve($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->approve((int) $id, $this->safeInput())));
    }

    /** Reject return. @agent-use: PATCH /api/returns/{id}/reject */
    public function reject($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->reject((int) $id, $this->safeInput())));
    }

    /** Complete return. @agent-use: PATCH /api/returns/{id}/complete */
    public function complete($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->complete((int) $id, $this->safeInput())));
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

    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                return $json;
            }
        } catch (\Throwable $e) {
        }
        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
