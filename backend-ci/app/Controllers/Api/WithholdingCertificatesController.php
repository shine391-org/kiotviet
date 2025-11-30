<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Taxes\WithholdingAdvancedService;
use CodeIgniter\API\ResponseTrait;

/**
 * Withholding certificates API.
 *
 * @agent-controller: WithholdingCertificates
 * @agent-pattern: Thin controller
 */
class WithholdingCertificatesController extends BaseController
{
    use ResponseTrait;

    protected WithholdingAdvancedService $service;

    public function __construct()
    {
        $this->service = service('withholdingAdvancedService');
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createCertificate($payload)));
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
