<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Projects\TimesheetService;
use CodeIgniter\API\ResponseTrait;

/**
 * Timesheets API.
 *
 * @agent-controller: Timesheets
 * @agent-pattern: Thin controller
 */
class TimesheetsController extends BaseController
{
    use ResponseTrait;

    protected TimesheetService $service;

    public function __construct()
    {
        $this->service = service('timesheetService');
    }

    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->show((int) $id)));
    }

    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->create($payload)));
    }

    public function submit($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->submit((int) $id, $payload)));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
