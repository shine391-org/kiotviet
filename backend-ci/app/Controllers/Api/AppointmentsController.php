<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Appointments\AppointmentService;
use CodeIgniter\API\ResponseTrait;

/**
 * Appointments API.
 *
 * @agent-controller: Appointments
 * @agent-pattern: Thin controller - delegates to service
 */
class AppointmentsController extends BaseController
{
    use ResponseTrait;

    protected AppointmentService $service;

    public function __construct()
    {
        $this->service = service('appointmentService');
    }

    /**
     * Schedule appointment.
     *
     * @agent-use: POST /api/appointments
     * @agent-pattern: Thin controller create
     */
    public function schedule()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->schedule($payload)));
    }

    /**
     * Reschedule appointment.
     *
     * @agent-use: POST /api/appointments/{id}/reschedule
     * @agent-pattern: Thin controller action
     */
    public function reschedule($id)
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respond($this->service->reschedule((int) $id, $payload)));
    }

    /**
     * Cancel appointment.
     *
     * @agent-use: POST /api/appointments/{id}/cancel
     * @agent-pattern: Thin controller action
     */
    public function cancel($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->cancel((int) $id)));
    }

    /**
     * View appointment.
     *
     * @agent-use: GET /api/appointments/{id}
     * @agent-pattern: Thin controller show
     */
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
