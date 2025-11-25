<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Webhook subscriptions table.
 *
 * @agent-model: webhook_subscriptions
 */
class WebhookSubscriptionModel extends Model
{
    protected $table = 'db_webhook_subscriptions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'event',
        'target_url',
        'secret',
        'is_active',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
}
