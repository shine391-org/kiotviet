<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Task schema.
 *
 * @agent-model: tasks
 * @agent-pattern: CI4 model
 */
class TaskModel extends Model
{
    protected $table = 'tasks';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'project_id',
        'parent_id',
        'task_name',
        'status',
        'progress',
        'estimated_hours',
        'actual_hours',
        'start_date',
        'due_date',
        'assigned_to',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
