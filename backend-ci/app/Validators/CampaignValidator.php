<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate campaigns.
 *
 * @agent-validator: Campaign
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class CampaignValidator
{
    public function validate(array $input): array
    {
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('name is required');
        }
        $budget = isset($input['budget']) ? (float) $input['budget'] : 0;
        if ($budget < 0) {
            throw new InvalidArgumentException('budget must be >= 0');
        }
        return [
            'name' => $name,
            'status' => $input['status'] ?? 'draft',
            'source' => isset($input['source']) ? trim((string) $input['source']) : null,
            'budget' => $budget,
            'start_date' => $input['start_date'] ?? null,
            'end_date' => $input['end_date'] ?? null,
        ];
    }
}
