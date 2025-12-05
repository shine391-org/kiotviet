<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\POS\POSProfileService;
use CodeIgniter\API\ResponseTrait;

/**
 * @agent-controller: POS profiles
 * @agent-pattern: Thin controller - routing only
 */
class POSProfilesController extends BaseController
{
    use ResponseTrait;

    protected POSProfileService $service;

    public function __construct()
    {
        $this->service = service('posProfileService');
    }

    /** Create POS profile. @agent-use: POST /api/pos/profiles */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    /** Update POS profile. @agent-use: PUT /api/pos/profiles/{id} */
    public function update($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $payload)));
    }

    /** Show POS profile. @agent-use: GET /api/pos/profiles/{id} */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->show((int) $id)));
    }

    /** Resolve profile for current user. @agent-use: GET /api/pos/profiles/resolve */
    public function resolve()
    {
        $params = $this->request->getGet();
        return $this->wrap(fn () => $this->respond($this->service->resolve($params)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
