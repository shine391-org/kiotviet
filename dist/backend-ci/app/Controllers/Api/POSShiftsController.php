<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\POS\POSShiftService;
use CodeIgniter\API\ResponseTrait;

/**
 * @agent-controller: POS shifts
 * @agent-pattern: Thin controller - routing only
 */
class POSShiftsController extends BaseController
{
    use ResponseTrait;

    protected POSShiftService $service;

    public function __construct()
    {
        $this->service = service('posShiftService');
    }

    /** Open shift. @agent-use: POST /api/pos/shifts/open */
    public function open()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->open($payload)));
    }

    /** Close shift. @agent-use: POST /api/pos/shifts/close */
    public function close()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->close($payload)));
    }

    /** Get current open shift for user/branch. @agent-use: GET /api/pos/shifts/current */
    public function current()
    {
        $userId = (int) ($this->request->getGet('user_id') ?? 0);
        $branchId = $this->request->getGet('branch_id') !== null ? (int) $this->request->getGet('branch_id') : null;
        return $this->wrap(fn () => $this->respond([
            'success' => true,
            'data' => $this->service->requireOpenShift($userId, $branchId),
        ]));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
