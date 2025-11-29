<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\CRM\OpportunityService;
use CodeIgniter\API\ResponseTrait;

/** Opportunities API. @agent-controller: Opportunities @agent-pattern: Thin controller */
class OpportunitiesController extends BaseController
{
    use ResponseTrait;

    protected OpportunityService $service;

    public function __construct()
    {
        $this->service = service('opportunityService');
    }

    /** Create opportunity. @agent-use: POST /api/opportunities */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** Update stage. @agent-use: POST /api/opportunities/{id}/stage */
    public function updateStage($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        $stage = $payload['stage'] ?? '';
        $prob = isset($payload['probability']) ? (int) $payload['probability'] : null;
        return $this->wrap(fn () => $this->respond($this->service->updateStage((int) $id, $stage, $prob)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
