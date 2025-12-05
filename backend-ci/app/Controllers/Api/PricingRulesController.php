<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Pricing\PricingRuleService;
use CodeIgniter\API\ResponseTrait;

/** Pricing rules API. @agent-controller: Pricing rules @agent-pattern: Thin controller */
class PricingRulesController extends BaseController
{
    use ResponseTrait;

    protected PricingRuleService $service;

    public function __construct()
    {
        $this->service = service('pricingRuleService');
    }

    /** List rules. */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Create rule. */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** Update rule. */
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
