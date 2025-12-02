<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Notification rule schema.
 *
 * @agent-model: notification_rules
 * @agent-pattern: CI4 model
 */
class NotificationRuleModel extends Model
{
    protected $table = 'notification_rules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'event_type',
        'channel',
        'template',
        'is_active',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
