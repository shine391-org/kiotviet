<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Portal\PortalService;
use CodeIgniter\API\ResponseTrait;

/**
 * Portal user admin API.
 *
 * @agent-controller: PortalUsers
 * @agent-pattern: Thin controller
 */
class PortalUsersController extends BaseController
{
    use ResponseTrait;

    protected PortalService $service;

    public function __construct()
    {
        $this->service = service('portalService');
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createUser($payload)));
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
