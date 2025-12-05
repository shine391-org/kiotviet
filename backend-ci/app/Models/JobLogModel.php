<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Job log schema.
 *
 * @agent-model: job_logs
 * @agent-pattern: CI4 model
 */
class JobLogModel extends Model
{
    protected $table = 'job_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'job_id',
        'status',
        'message',
        'created_at',
    ];
    protected $useSoftDeletes = false;
}
