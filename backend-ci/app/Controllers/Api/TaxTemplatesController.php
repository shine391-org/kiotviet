<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\POS\POSTaxService;
use App\Repositories\Taxes\TaxTemplateRepository;
use App\Validators\TaxTemplateValidator;
use CodeIgniter\API\ResponseTrait;

/**
 * @agent-controller: Tax templates
 * @agent-pattern: Thin controller - routing only
 */
class TaxTemplatesController extends BaseController
{
    use ResponseTrait;

    protected TaxTemplateRepository $repo;
    protected TaxTemplateValidator $validator;
    protected POSTaxService $taxService;

    public function __construct()
    {
        $this->repo = service('taxTemplateRepository');
        $this->validator = service('taxTemplateValidator');
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
            $data = $this->validator->validateCreate($payload);
            $created = $this->repo->create($data);
            return $this->respondCreated(['success' => true, 'data' => $created]);
        });
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
