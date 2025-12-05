<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Approvals\ApprovalRuleService;
use CodeIgniter\API\ResponseTrait;

/** Approval rules API. @agent-controller: Approval rules @agent-pattern: Thin controller - routing only */
class ApprovalRulesController extends BaseController
{
    use ResponseTrait;

    protected ApprovalRuleService $service;

    public function __construct()
    {
        $this->service = service('approvalRuleService');
    }

    /** List rules. @agent-use: GET /api/approval-rules */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Create rule. @agent-use: POST /api/approval-rules */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** Update rule. @agent-use: PUT /api/approval-rules/{id} */
    public function update($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $payload)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
