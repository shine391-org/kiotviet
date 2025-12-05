<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Scheduler rule schema.
 *
 * @agent-model: scheduler_rules
 * @agent-pattern: CI4 model
 */
class SchedulerRuleModel extends Model
{
    protected $table = 'scheduler_rules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'cron_expression',
        'handler',
        'is_active',
        'last_run_at',
        'next_run_at',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
