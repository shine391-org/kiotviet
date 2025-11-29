<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Manufacturing\SubcontractingService;
use CodeIgniter\API\ResponseTrait;

/**
 * Subcontracting API.
 *
 * @agent-controller: Subcontracting
 * @agent-pattern: Thin controller - routing only
 */
class SubcontractingController extends BaseController
{
    use ResponseTrait;

    protected SubcontractingService $service;

    public function __construct()
    {
        $this->service = service('subcontractingService');
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    public function issue($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->issueMaterials((int) $id)));
    }

    public function receive($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->receiveProduct((int) $id)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
