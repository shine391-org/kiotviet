<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate ticket communications.
 *
 * @agent-validator: Communication
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class CommunicationValidator
{
    private array $allowedTypes = ['note', 'email'];

    public function validate(array $input): array
    {
        $type = strtolower(trim((string) ($input['type'] ?? 'note')));
        if (! in_array($type, $this->allowedTypes, true)) {
            throw new InvalidArgumentException('invalid communication type');
        }

        $content = trim((string) ($input['content'] ?? ''));
        if ($content === '') {
            throw new InvalidArgumentException('content is required');
        }

        $attachments = $input['attachments'] ?? null;
        if (is_array($attachments)) {
            $attachments = json_encode($attachments);
        }

        return [
            'ticket_id' => isset($input['ticket_id']) ? (int) $input['ticket_id'] : null,
            'type' => $type,
            'content' => $content,
            'attachments' => $attachments,
            'created_by' => isset($input['created_by']) ? (int) $input['created_by'] : null,
        ];
    }
}
