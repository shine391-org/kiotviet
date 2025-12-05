<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\CustomerGroups\CustomerGroupService;
use CodeIgniter\API\ResponseTrait;

/** Customer Groups API. @agent-controller: Customer Groups @agent-pattern: Thin controller */
class CustomerGroupsController extends BaseController
{
    use ResponseTrait;

    protected CustomerGroupService $service;

    public function __construct()
    {
        $this->service = new CustomerGroupService();
    }

    /** List customer groups. @agent-use: GET /api/customer-groups */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Show single customer group. @agent-use: GET /api/customer-groups/{id} */
    public function show($id = null)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    /** Create customer group. @agent-use: POST /api/customer-groups */
    public function create()
    {
        $data = $this->safeInput();
        return $this->wrap(fn () => $this->respondCreated($this->service->create($data)));
    }

    /** Update customer group. @agent-use: PUT /api/customer-groups/{id} */
    public function update($id)
    {
        $data = $this->safeInput();
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $data)));
    }

    /** Delete customer group. @agent-use: DELETE /api/customer-groups/{id} */
    public function delete($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->delete((int) $id)));
    }

    /** Shared try/catch wrapper. */
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

    /** Safely fetch request body. */
    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                return $json;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
