<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Accounting\BankReconciliationService;
use CodeIgniter\API\ResponseTrait;

/**
 * Bank reconciliation API.
 *
 * @agent-controller: BankReconciliations
 * @agent-pattern: Thin controller - routing only
 */
class BankReconciliationsController extends BaseController
{
    use ResponseTrait;

    protected BankReconciliationService $service;

    public function __construct()
    {
        $this->service = service('bankReconciliationService');
    }

    /** @agent-use: POST /api/bank-statements/import */
    public function import()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->import($payload)));
    }

    /** @agent-use: POST /api/bank-reconciliations/{id}/match */
    public function match($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $paymentId = isset($payload['payment_entry_id']) ? (int) $payload['payment_entry_id'] : 0;
        $amount = isset($payload['amount']) ? (float) $payload['amount'] : 0;
        return $this->wrap(fn () => $this->respond($this->service->reconcile((int) $id, $paymentId, $amount)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
