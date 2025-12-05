<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Accounting\AccountingService;
use CodeIgniter\API\ResponseTrait;

/**
 * GL entries API.
 *
 * @agent-controller: GLEntries
 * @agent-pattern: Thin controller - routing only
 */
class GLEntriesController extends BaseController
{
    use ResponseTrait;

    protected AccountingService $service;

    public function __construct()
    {
        $this->service = service('accountingService');
    }

    /** @agent-use: POST /api/gl/journal */
    public function postJournal()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->postJournal($payload)));
    }

    /** @agent-use: GET /api/gl */
    public function index()
    {
        $filters = $this->request->getGet();
        return $this->wrap(fn () => $this->respond($this->service->list($filters)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
