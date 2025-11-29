<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate email campaigns.
 *
 * @agent-validator: Email campaign
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class EmailCampaignValidator
{
    public function validate(array $input): array
    {
        $subject = trim((string) ($input['subject'] ?? ''));
        if ($subject === '') {
            throw new InvalidArgumentException('subject is required');
        }
        return [
            'campaign_id' => isset($input['campaign_id']) ? (int) $input['campaign_id'] : null,
            'subject' => $subject,
            'template' => $input['template'] ?? null,
            'schedule_at' => $input['schedule_at'] ?? null,
            'status' => $input['status'] ?? 'draft',
        ];
    }
}
