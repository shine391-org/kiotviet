<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\CRM\QuotationService;
use App\Services\CRM\OpportunityService;
use CodeIgniter\API\ResponseTrait;

/** Quotations API. @agent-controller: Quotations @agent-pattern: Thin controller */
class QuotationsController extends BaseController
{
    use ResponseTrait;

    protected QuotationService $service;
    protected OpportunityService $opportunities;

    public function __construct()
    {
        $this->service = service('quotationService');
        $this->opportunities = service('opportunityService');
    }

    /** Create quotation. @agent-use: POST /api/quotations */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** Create from opportunity. @agent-use: POST /api/opportunities/{id}/quote */
    public function createFromOpportunity($id)
    {
        $opp = $this->opportunities->find((int) $id);
        if (! $opp) {
            return $this->failNotFound('Opportunity not found');
        }
        return $this->wrap(fn () => $this->respondCreated($this->service->createFromOpportunity($opp)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
