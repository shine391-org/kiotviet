<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Quality\QualityInspectionService;
use CodeIgniter\API\ResponseTrait;
use InvalidArgumentException;
use RuntimeException;

/**
 * Quality inspections API.
 *
 * @agent-controller: Quality management
 * @agent-pattern: Thin controller - routing only
 */
class QualityInspectionsController extends BaseController
{
    use ResponseTrait;

    protected QualityInspectionService $service;

    public function __construct()
    {
        $this->service = service('qualityInspectionService');
    }

    /** List parameters. @agent-use: GET /api/quality-parameters */
    public function parameters()
    {
        return $this->wrap(fn () => $this->respond($this->service->listParameters($this->request->getGet())));
    }

    /** Create parameter. @agent-use: POST /api/quality-parameters */
    public function createParameter()
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createParameter($data)));
    }

    /** Update parameter. @agent-use: PUT /api/quality-parameters/{id} */
    public function updateParameter($id)
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->updateParameter((int) $id, $data)));
    }

    /** Delete parameter. @agent-use: DELETE /api/quality-parameters/{id} */
    public function deleteParameter($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->deleteParameter((int) $id)));
    }

    /** List inspections. @agent-use: GET /api/quality-inspections */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->listInspections($this->request->getGet())));
    }

    /** Show inspection. @agent-use: GET /api/quality-inspections/{id} */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->show((int) $id)));
    }

    /** Create inspection. @agent-use: POST /api/quality-inspections */
    public function create()
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createInspection($data)));
    }

    /** Submit inspection. @agent-use: POST /api/quality-inspections/{id}/submit */
    public function submit($id)
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->submitInspection((int) $id, $data)));
    }

    /** Approve inspection. @agent-use: POST /api/quality-inspections/{id}/approve */
    public function approve($id)
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->approveInspection((int) $id, $data)));
    }

    /** Reject inspection. @agent-use: POST /api/quality-inspections/{id}/reject */
    public function reject($id)
    {
        $data = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->rejectInspection((int) $id, $data)));
    }

    /** Shared exception wrapper. */
    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
