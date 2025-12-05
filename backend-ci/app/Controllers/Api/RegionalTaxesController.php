<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Taxes\RegionalTaxService;
use CodeIgniter\API\ResponseTrait;

/**
 * Regional taxes API.
 *
 * @agent-controller: RegionalTaxes
 * @agent-pattern: Thin controller
 */
class RegionalTaxesController extends BaseController
{
    use ResponseTrait;

    protected RegionalTaxService $service;

    public function __construct()
    {
        $this->service = service('regionalTaxService');
    }

    public function setRule()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->setRule($payload)));
    }

    public function preview()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->preview($payload)));
    }

    public function einvoice()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->logEInvoice($payload)));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->fail($e->getMessage(), 400);
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
