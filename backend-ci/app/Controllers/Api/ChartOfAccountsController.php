<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Accounting\COAService;
use CodeIgniter\API\ResponseTrait;

/**
 * Chart of accounts API.
 *
 * @agent-controller: ChartOfAccounts
 * @agent-pattern: Thin controller - routing only
 */
class ChartOfAccountsController extends BaseController
{
    use ResponseTrait;

    protected COAService $service;

    public function __construct()
    {
        $this->service = service('coaService');
    }

    /** @agent-use: POST /api/chart-of-accounts */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** @agent-use: GET /api/chart-of-accounts/{id} */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    /** @agent-use: GET /api/chart-of-accounts/{id}/children */
    public function children($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->listChildren((int) $id)));
    }

    /** @agent-use: GET /api/chart-of-accounts */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->listChildren(null)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
