<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate appointments.
 *
 * @agent-validator: Appointment
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class AppointmentValidator
{
    public function validate(array $input): array
    {
        $start = $input['start_time'] ?? null;
        $end = $input['end_time'] ?? null;
        if (! $start || ! $end) {
            throw new InvalidArgumentException('start_time and end_time are required');
        }
        $startDt = new \DateTime($start);
        $endDt = new \DateTime($end);
        if ($endDt <= $startDt) {
            throw new InvalidArgumentException('end_time must be after start_time');
        }
        return [
            'customer_id' => isset($input['customer_id']) ? (int) $input['customer_id'] : null,
            'lead_id' => isset($input['lead_id']) ? (int) $input['lead_id'] : null,
            'contract_id' => isset($input['contract_id']) ? (int) $input['contract_id'] : null,
            'start_time' => $startDt->format('Y-m-d H:i:s'),
            'end_time' => $endDt->format('Y-m-d H:i:s'),
            'status' => $input['status'] ?? 'scheduled',
            'notes' => isset($input['notes']) ? trim((string) $input['notes']) : null,
        ];
    }
}
