<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate notification rules/triggers.
 *
 * @agent-validator: Notification
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class NotificationValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateRule(array $input): array
    {
        $data = $this->run($input, [
            'name' => 'required|string|max_length[150]',
            'event_type' => 'required|string|max_length[80]',
            'channel' => 'permit_empty|string|max_length[50]',
            'template' => 'required|string',
            'is_active' => 'permit_empty|integer|in_list[0,1]',
        ]);
        $data['channel'] = $data['channel'] ?? 'email';
        $data['is_active'] = isset($data['is_active']) ? (int) $data['is_active'] : 1;
        return $data;
    }

    public function validateTrigger(array $input): array
    {
        return $this->run($input, [
            'event_type' => 'required|string|max_length[80]',
            'entity_type' => 'permit_empty|string|max_length[80]',
            'entity_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'payload' => 'permit_empty',
        ]);
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
