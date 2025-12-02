<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Job queue schema.
 *
 * @agent-model: job_queue
 * @agent-pattern: CI4 model
 */
class JobModel extends Model
{
    protected $table = 'job_queue';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'payload',
        'status',
        'attempts',
        'max_attempts',
        'next_run_at',
        'last_error',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
