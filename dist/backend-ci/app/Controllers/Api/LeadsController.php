<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\CRM\LeadService;
use CodeIgniter\API\ResponseTrait;

/** Leads API. @agent-controller: Leads @agent-pattern: Thin controller */
class LeadsController extends BaseController
{
    use ResponseTrait;

    protected LeadService $service;

    public function __construct()
    {
        $this->service = service('leadService');
    }

    /** Create lead. @agent-use: POST /api/leads */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** Convert to customer. @agent-use: POST /api/leads/{id}/convert */
    public function convert($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->convertToCustomer((int) $id)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
