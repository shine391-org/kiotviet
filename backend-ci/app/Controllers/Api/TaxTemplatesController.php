<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\POS\POSTaxService;
use App\Services\Taxes\TaxTemplateService;
use CodeIgniter\API\ResponseTrait;

/**
 * @agent-controller: Tax templates
 * @agent-pattern: Thin controller - routing only
 */
class TaxTemplatesController extends BaseController
{
    use ResponseTrait;

    protected TaxTemplateService $service;
    protected POSTaxService $taxService;

    public function __construct()
    {
        $this->service = service('taxTemplateService');
        $this->taxService = service('posTaxService');
    }

    /** List templates. @agent-use: GET /api/tax-templates */
    public function index()
    {
        return $this->respond(['success' => true, 'data' => $this->repo->allActive()]);
    }

    /** Create template. @agent-use: POST /api/tax-templates */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(function () use ($payload) {
            $created = $this->service->create($payload);
            return $this->respondCreated($created);
        });
    }

    /** @agent-use: GET /api/tax-templates/{id} */
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
