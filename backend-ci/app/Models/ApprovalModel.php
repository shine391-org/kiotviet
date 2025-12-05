<?php

namespace App\Models;

use CodeIgniter\Model;

/** Approval instance model. @agent-model: approvals */
class ApprovalModel extends Model
{
    protected $table = 'approvals';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'entity_type','entity_id','order_id','status','approver_queue',
        'current_index','current_approver_id','requested_by','requested_at','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
