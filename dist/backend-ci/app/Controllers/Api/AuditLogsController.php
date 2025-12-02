<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Audits\AuditService;
use CodeIgniter\API\ResponseTrait;

/**
 * Audit log API.
 *
 * @agent-controller: Audit logs
 * @agent-pattern: Thin controller
 */
class AuditLogsController extends BaseController
{
    use ResponseTrait;

    protected AuditService $service;

    public function __construct()
    {
        $this->service = service('auditService');
    }

    /** @agent-use: GET /api/audit-logs @agent-pattern: List audit logs */
    public function index()
    {
        $userId = $this->request->getGet('user_id');
        $actorId = $userId !== null ? (int) $userId : null;
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet(), $actorId)));
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
