<?php

namespace App\Repositories\Notifications;

use App\Models\NotificationRuleModel;
use App\Models\NotificationModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Notification rule + log repository.
 *
 * @agent-repository: Notification
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class NotificationRuleRepository
{
    protected NotificationRuleModel $rules;
    protected NotificationModel $notifications;
    protected BaseConnection $db;

    public function __construct(?NotificationRuleModel $rules = null, ?NotificationModel $notifications = null, ?BaseConnection $db = null)
    {
        $this->rules = $rules ?? new NotificationRuleModel();
        $this->notifications = $notifications ?? new NotificationModel();
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function createRule(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->rules->insert($payload);
        $payload['id'] = (int) $this->rules->getInsertID();
        return $payload;
    }

    public function listRules(array $filters = []): array
    {
        $b = $this->rules->builder();
        if (! empty($filters['event_type'])) {
            $b->where('event_type', $filters['event_type']);
        }
        if (isset($filters['is_active'])) {
            $b->where('is_active', $filters['is_active']);
        }
        return $b->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();
    }

    public function activeRulesForEvent(string $event): array
    {
        return $this->rules->where('event_type', $event)->where('is_active', 1)->findAll();
    }

    public function logNotification(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->notifications->insert($payload);
        $payload['id'] = (int) $this->notifications->getInsertID();
        return $payload;
    }

    public function listNotifications(array $filters = []): array
    {
        $b = $this->notifications->builder();
        if (! empty($filters['event_type'])) {
            $b->where('event_type', $filters['event_type']);
        }
        return $b->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
