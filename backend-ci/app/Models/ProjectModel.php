<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Project schema.
 *
 * @agent-model: projects
 * @agent-pattern: CI4 model
 */
class ProjectModel extends Model
{
    protected $table = 'projects';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'project_name',
        'project_code',
        'customer_id',
        'status',
        'progress',
        'start_date',
        'end_date',
        'description',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
