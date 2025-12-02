<?php

namespace App\Validators;

use InvalidArgumentException;

/**
 * Validate support tickets.
 *
 * @agent-validator: SupportTicket
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class SupportTicketValidator
{
    private array $allowedStatuses = ['open', 'working', 'resolved', 'closed'];
    private array $allowedPriorities = ['low', 'medium', 'high'];

    public function validateCreate(array $input): array
    {
        $subject = trim((string) ($input['subject'] ?? ''));
        if ($subject === '') {
            throw new InvalidArgumentException('subject is required');
        }

        $priority = $this->normalizePriority($input['priority'] ?? 'medium');

        return [
            'subject' => $subject,
            'customer_id' => $this->normalizeId($input['customer_id'] ?? null, 'customer_id'),
            'lead_id' => $this->normalizeId($input['lead_id'] ?? null, 'lead_id'),
            'priority' => $priority,
            'status' => $this->normalizeStatus($input['status'] ?? 'open'),
            'assigned_to' => $this->normalizeAssignee($input['assigned_to'] ?? null),
            'description' => isset($input['description']) ? trim((string) $input['description']) : null,
        ];
    }

    public function validateUpdate(array $input): array
    {
        $data = [];
        if (isset($input['subject'])) {
            $subject = trim((string) $input['subject']);
            if ($subject === '') {
                throw new InvalidArgumentException('subject cannot be empty');
            }
            $data['subject'] = $subject;
        }
        if (isset($input['priority'])) {
            $data['priority'] = $this->normalizePriority($input['priority']);
        }
        if (array_key_exists('customer_id', $input)) {
            $data['customer_id'] = $this->normalizeId($input['customer_id'], 'customer_id');
        }
        if (array_key_exists('lead_id', $input)) {
            $data['lead_id'] = $this->normalizeId($input['lead_id'], 'lead_id');
        }
        if (array_key_exists('description', $input)) {
            $data['description'] = isset($input['description']) ? trim((string) $input['description']) : null;
        }
        if (array_key_exists('assigned_to', $input)) {
            $data['assigned_to'] = $this->normalizeAssignee($input['assigned_to']);
        }

        if (isset($input['status'])) {
            throw new InvalidArgumentException('status cannot be changed via update; use changeStatus');
        }

        return $data;
    }

    public function validateStatus(string $status): string
    {
        return $this->normalizeStatus($status);
    }

    public function validateAssignment($assigneeId): ?int
    {
        return $this->normalizeAssignee($assigneeId);
    }

    private function normalizeStatus(string $status): string
    {
        $value = strtolower(trim($status));
        if (! in_array($value, $this->allowedStatuses, true)) {
            throw new InvalidArgumentException('invalid status');
        }
        return $value;
    }

    private function normalizePriority(string $priority): string
    {
        $value = strtolower(trim($priority));
        if (! in_array($value, $this->allowedPriorities, true)) {
            throw new InvalidArgumentException('invalid priority');
        }
        return $value;
    }

    private function normalizeId($id, string $field): ?int
    {
        if ($id === null || $id === '') {
            return null;
        }
        $int = (int) $id;
        if ($int <= 0) {
            throw new InvalidArgumentException($field . ' must be positive');
        }
        return $int;
    }

    private function normalizeAssignee($assignee): ?int
    {
        if ($assignee === null || $assignee === '') {
            return null;
        }
        $int = (int) $assignee;
        if ($int <= 0) {
            throw new InvalidArgumentException('assigned_to must be positive');
        }
        return $int;
    }
}
