<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Accounting\CreditControlService;
use CodeIgniter\API\ResponseTrait;

/**
 * Credit limits API.
 *
 * @agent-controller: CreditLimits
 * @agent-pattern: Thin controller - routing only
 */
class CreditLimitsController extends BaseController
{
    use ResponseTrait;

    protected CreditControlService $service;

    public function __construct()
    {
        $this->service = service('creditControlService');
    }

    /** @agent-use: POST /api/credit-limits */
    public function upsert()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->upsertLimit($payload)));
    }

    /** @agent-use: POST /api/credit-check */
    public function check()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->check($payload)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
