<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Permissions\SharingService;
use CodeIgniter\API\ResponseTrait;

/**
 * Document share API.
 *
 * @agent-controller: Shares
 * @agent-pattern: Thin controller - ACL shares
 */
class SharesController extends BaseController
{
    use ResponseTrait;

    protected SharingService $service;

    public function __construct()
    {
        $this->service = service('sharingService');
    }

    /** @agent-use: GET /api/shares @agent-pattern: Guarded list shares */
    public function index()
    {
        $actorId = (int) ($this->request->getGet('actor_id') ?? $this->request->getGet('user_id') ?? 0);
        return $this->wrap(fn () => $this->respond($this->service->list($actorId, $this->request->getGet())));
    }

    /** @agent-use: POST /api/shares @agent-pattern: Share document */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        $actorId = (int) ($payload['actor_id'] ?? $payload['user_id'] ?? 0);
        return $this->wrap(fn () => $this->respondCreated($this->service->share($actorId, $payload)));
    }

    /** @agent-use: DELETE /api/shares/{id} @agent-pattern: Unshare document */
    public function delete($id)
    {
        $actorId = (int) ($this->request->getGet('actor_id') ?? $this->request->getGet('user_id') ?? 0);
        return $this->wrap(fn () => $this->respond($this->service->unshare($actorId, (int) $id)));
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
