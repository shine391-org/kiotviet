<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Projects\ProjectService;
use App\Services\Projects\TimesheetService;
use CodeIgniter\API\ResponseTrait;

/**
 * Projects API.
 *
 * @agent-controller: Projects
 * @agent-pattern: Thin controller - routing only
 */
class ProjectsController extends BaseController
{
    use ResponseTrait;

    protected ProjectService $service;
    protected TimesheetService $timesheets;

    public function __construct()
    {
        $this->service = service('projectService');
        $this->timesheets = service('timesheetService');
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

    public function update($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $payload)));
    }

    public function timesheetSummary($projectId)
    {
        return $this->wrap(fn () => $this->respond($this->timesheets->summaryByProject((int) $projectId)));
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
