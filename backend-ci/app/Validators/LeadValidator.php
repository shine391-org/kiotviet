<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate leads.
 *
 * @agent-validator: Lead
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class LeadValidator
{
    public function validate(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('name is required');
        }
        $email = isset($input['email']) ? trim((string) $input['email']) : null;
        $phone = isset($input['phone']) ? trim((string) $input['phone']) : null;
        return [
            'lead_number' => $input['lead_number'] ?? null,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'source' => isset($input['source']) ? trim((string) $input['source']) : null,
            'status' => $input['status'] ?? 'new',
            'company' => isset($input['company']) ? trim((string) $input['company']) : null,
            'notes' => isset($input['notes']) ? trim((string) $input['notes']) : null,
        ];
    }
}
