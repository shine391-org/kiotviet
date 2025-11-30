<?php

namespace App\Services\Notifications;

use App\Repositories\Notifications\NotificationRuleRepository;
use App\Validators\NotificationValidator;
use RuntimeException;

/**
 * Notification service (channel-agnostic stub).
 *
 * @agent-service: Notification
 * @agent-pattern: Rule + trigger
 * @agent-reusable: MEDIUM
 */
class NotificationService
{
    protected NotificationRuleRepository $repo;
    protected NotificationValidator $validator;

    public function __construct(?NotificationRuleRepository $repo = null, ?NotificationValidator $validator = null)
    {
        $this->repo = $repo ?? new NotificationRuleRepository();
        $this->validator = $validator ?? new NotificationValidator();
    }

    /** @agent-use: POST /api/notification-rules */
    public function createRule(array $input): array
    {
        $data = $this->validator->validateRule($input);
        $rule = $this->repo->createRule($data);
        return ['success' => true, 'data' => $rule];
    }

    /** @agent-use: GET /api/notification-rules */
    public function listRules(array $filters = []): array
    {
        return ['success' => true, 'data' => $this->repo->listRules($filters)];
    }

    /**
     * Trigger notifications for event.
     *
     * @agent-use: Internal hook /api/notifications/trigger
     */
    public function trigger(array $input): array
    {
        $data = $this->validator->validateTrigger($input);
        $rules = $this->repo->activeRulesForEvent($data['event_type']);
        $logs = [];
        foreach ($rules as $rule) {
            $logs[] = $this->repo->logNotification([
                'rule_id' => $rule['id'],
                'event_type' => $data['event_type'],
                'entity_type' => $data['entity_type'] ?? null,
                'entity_id' => $data['entity_id'] ?? null,
                'payload' => json_encode($data['payload'] ?? []),
                'status' => 'queued',
            ]);
        }
        return ['success' => true, 'data' => $logs];
    }
}
