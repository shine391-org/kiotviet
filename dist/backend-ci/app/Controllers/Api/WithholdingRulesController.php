<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Accounting\WithholdingService;
use CodeIgniter\API\ResponseTrait;

/**
 * Withholding rules API.
 *
 * @agent-controller: WithholdingRules
 * @agent-pattern: Thin controller - routing only
 */
class WithholdingRulesController extends BaseController
{
    use ResponseTrait;

    protected WithholdingService $service;

    public function __construct()
    {
        $this->service = service('withholdingService');
    }

    /** @agent-use: POST /api/withholding-rules */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** @agent-use: GET /api/withholding-rules/(:num)/apply?amount= */
    public function apply($id)
    {
        $amount = (float) ($this->request->getGet('amount') ?? 0);
        return $this->wrap(fn () => $this->respond($this->service->apply((int) $id, $amount)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
