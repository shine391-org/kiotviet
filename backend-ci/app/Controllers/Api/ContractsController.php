<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Contracts\ContractService;
use CodeIgniter\API\ResponseTrait;

/**
 * Contracts API.
 *
 * @agent-controller: Contracts
 * @agent-pattern: Thin controller - delegates to service
 */
class ContractsController extends BaseController
{
    use ResponseTrait;

    protected ContractService $service;

    public function __construct()
    {
        $this->service = service('contractService');
    }

    /**
     * Create contract with terms.
     *
     * @agent-use: POST /api/contracts
     * @agent-pattern: Thin controller create
     */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /**
     * Show contract.
     *
     * @agent-use: GET /api/contracts/{id}
     * @agent-pattern: Thin controller show
     */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    /**
     * Activate contract.
     *
     * @agent-use: POST /api/contracts/{id}/activate
     * @agent-pattern: Thin controller action
     */
    public function activate($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->activate((int) $id)));
    }

    /**
     * Close contract.
     *
     * @agent-use: POST /api/contracts/{id}/close
     * @agent-pattern: Thin controller action
     */
    public function close($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->close((int) $id)));
    }

    /**
     * Renew stub endpoint.
     *
     * @agent-use: POST /api/contracts/{id}/renew
     * @agent-pattern: Thin controller action
     */
    public function renew($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->renew((int) $id)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
