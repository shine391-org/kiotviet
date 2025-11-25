<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Webhook delivery queue table.
 *
 * @agent-model: webhook_events
 */
class WebhookEventModel extends Model
{
    protected $table = 'db_webhook_events';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'event',
        'payload',
        'status',
        'attempts',
        'last_error',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = false;
}
