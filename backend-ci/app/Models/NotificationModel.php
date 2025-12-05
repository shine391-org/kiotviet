<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Notification log schema.
 *
 * @agent-model: notifications
 * @agent-pattern: CI4 model
 */
class NotificationModel extends Model
{
    protected $table = 'notifications';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'rule_id',
        'event_type',
        'entity_type',
        'entity_id',
        'payload',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
