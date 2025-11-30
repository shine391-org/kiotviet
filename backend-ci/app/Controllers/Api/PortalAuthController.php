<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Portal\PortalService;
use CodeIgniter\API\ResponseTrait;

/**
 * Portal auth API.
 *
 * @agent-controller: PortalAuth
 * @agent-pattern: Thin controller
 */
class PortalAuthController extends BaseController
{
    use ResponseTrait;

    protected PortalService $service;

    public function __construct()
    {
        $this->service = service('portalService');
    }

    public function login()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->login($payload)));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->failUnauthorized($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
