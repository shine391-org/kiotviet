<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Pricing\PricingService;
use CodeIgniter\API\ResponseTrait;

/** Pricing preview API. @agent-controller: Pricing @agent-pattern: Thin controller */
class PricingController extends BaseController
{
    use ResponseTrait;

    protected PricingService $service;

    public function __construct()
    {
        $this->service = service('pricingService');
    }

    /** Preview price. @agent-use: POST /api/pricing/preview */
    public function preview()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->getPrice($payload)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
