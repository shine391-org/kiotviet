<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Assignment rule schema.
 *
 * @agent-model: assignment_rules
 * @agent-pattern: CI4 model
 */
class AssignmentRuleModel extends Model
{
    protected $table = 'assignment_rules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'entity_type',
        'strategy',
        'team_members',
        'last_assigned_id',
        'is_active',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
