<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Accounting\LandedCostService;
use CodeIgniter\API\ResponseTrait;

/**
 * Landed cost API.
 *
 * @agent-controller: LandedCosts
 * @agent-pattern: Thin controller - routing only
 */
class LandedCostsController extends BaseController
{
    use ResponseTrait;

    protected LandedCostService $service;

    public function __construct()
    {
        $this->service = service('landedCostService');
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
