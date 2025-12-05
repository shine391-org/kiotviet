<?php

namespace App\Services\Appointments;

use App\Repositories\Appointments\AppointmentRepository;
use App\Validators\AppointmentValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-service: Appointments
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class AppointmentService
{
    protected AppointmentRepository $repo;
    protected AppointmentValidator $validator;

    public function __construct(?AppointmentRepository $repo = null, ?AppointmentValidator $validator = null)
    {
        $this->repo = $repo ?? new AppointmentRepository();
        $this->validator = $validator ?? new AppointmentValidator();
    }

    /**
     * Schedule appointment with conflict guard.
     *
     * @agent-use: Service entry for POST /api/appointments
     * @agent-pattern: Validate -> conflict check -> create
     */
    public function schedule(array $input): array
    {
        $data = $this->validator->validate($input);
        $start = new \DateTime($data['start_time']);
        $end = new \DateTime($data['end_time']);
        if ($this->repo->hasConflict($start, $end)) {
            throw new InvalidArgumentException('Appointment conflict');
        }
        $appt = $this->repo->create($data);
        return ['success' => true, 'data' => $appt];
    }

    /**
     * Reschedule appointment with conflict guard.
     *
     * @agent-use: Service entry for POST /api/appointments/{id}/reschedule
     * @agent-pattern: Validate -> conflict check -> update
     */
    public function reschedule(int $id, array $input): array
    {
        $appt = $this->repo->findById($id);
        if (! $appt) {
            throw new RuntimeException('Appointment not found');
        }
        $data = $this->validator->validate($input);
        $start = new \DateTime($data['start_time']);
        $end = new \DateTime($data['end_time']);
        if ($this->repo->hasConflict($start, $end, $id)) {
            throw new InvalidArgumentException('Appointment conflict');
        }
        $this->repo->updateSchedule($id, $data);
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    /**
     * Cancel appointment.
     *
     * @agent-use: Service entry for POST /api/appointments/{id}/cancel
     * @agent-pattern: Status transition
     */
    public function cancel(int $id): array
    {
        $appt = $this->repo->findById($id);
        if (! $appt) {
            throw new RuntimeException('Appointment not found');
        }
        $this->repo->updateStatus($id, 'cancelled');
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    /**
     * Get appointment details.
     *
     * @agent-use: Service entry for GET /api/appointments/{id}
     * @agent-pattern: Repository fetch
     */
    public function get(int $id): array
    {
        $appt = $this->repo->findById($id);
        if (! $appt) {
            throw new RuntimeException('Appointment not found');
        }
        return ['success' => true, 'data' => $appt];
    }
}
