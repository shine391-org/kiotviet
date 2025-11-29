<?php

namespace App\Models;

use CodeIgniter\Model;

/** Approval action log model. @agent-model: approval_actions */
class ApprovalActionModel extends Model
{
    protected $table = 'approval_actions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'approval_id','action','actor_id','notes','created_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
