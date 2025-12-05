<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Assignment log schema.
 *
 * @agent-model: assignment_logs
 * @agent-pattern: CI4 model
 */
class AssignmentLogModel extends Model
{
    protected $table = 'assignment_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'assignment_rule_id',
        'entity_type',
        'entity_id',
        'assignee_id',
        'created_at',
    ];
    protected $useSoftDeletes = false;
}
