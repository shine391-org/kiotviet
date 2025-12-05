<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Audit log model.
 *
 * @agent-model: audit_logs
 * @agent-pattern: CI4 model
 */
class AuditLogModel extends Model
{
    protected $table = 'audit_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'company_id',
        'entity_type',
        'entity_id',
        'action',
        'changes',
        'actor_id',
        'created_at',
    ];
}
