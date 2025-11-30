<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Companies\CompanyService;
use CodeIgniter\API\ResponseTrait;

/**
 * Company management API (CRUD + permissions).
 *
 * @agent-controller: Companies
 * @agent-pattern: Thin controller - routing only
 */
class CompaniesController extends BaseController
{
    use ResponseTrait;

    protected CompanyService $service;

    public function __construct()
    {
        $this->service = service('companyService');
    }

    /** @agent-use: GET /api/companies @agent-pattern: List companies */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** @agent-use: POST /api/companies @agent-pattern: Thin create */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** @agent-use: PUT /api/companies/{id} @agent-pattern: Thin update */
    public function update($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $payload)));
    }

    /** @agent-use: POST /api/companies/{id}/permissions @agent-pattern: Assign permission */
    public function assignPermission($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $actorId = isset($payload['actor_id']) ? (int) $payload['actor_id'] : null;
        return $this->wrap(fn () => $this->respond($this->service->assignPermission((int) $id, $payload, $actorId)));
    }

    /** @agent-use: GET /api/companies/{id}/permissions @agent-pattern: List permissions */
    public function permissions($id)
    {
        $actorId = $this->request->getGet('user_id');
        $actorId = $actorId !== null ? (int) $actorId : null;
        return $this->wrap(fn () => $this->respond($this->service->permissions((int) $id, $actorId)));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 403);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
