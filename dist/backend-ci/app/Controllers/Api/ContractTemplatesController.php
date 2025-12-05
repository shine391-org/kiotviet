<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Contracts\ContractService;
use CodeIgniter\API\ResponseTrait;

/**
 * Contract templates API.
 *
 * @agent-controller: ContractTemplates
 * @agent-pattern: Thin controller - delegates to service
 */
class ContractTemplatesController extends BaseController
{
    use ResponseTrait;

    protected ContractService $service;

    public function __construct()
    {
        $this->service = service('contractService');
    }

    /**
     * Create contract template.
     *
     * @agent-use: POST /api/contract-templates
     * @agent-pattern: Thin controller create
     */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createTemplate($payload)));
    }

    /**
     * Show template details.
     *
     * @agent-use: GET /api/contract-templates/{id}
     * @agent-pattern: Thin controller show
     */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->getTemplate((int) $id)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
