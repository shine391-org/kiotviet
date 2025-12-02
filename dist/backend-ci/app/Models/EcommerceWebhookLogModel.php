<?php

namespace App\Models;

use CodeIgniter\Model;

/** Ecommerce webhook log schema. @agent-model: ecommerce_webhook_logs */
class EcommerceWebhookLogModel extends Model
{
    protected $table = 'ecommerce_webhook_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['source', 'event_type', 'idempotency_key', 'status', 'payload_hash', 'processed_at', 'created_at', 'updated_at'];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
