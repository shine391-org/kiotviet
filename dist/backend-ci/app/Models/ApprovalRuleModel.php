<?php

namespace App\Models;

use CodeIgniter\Model;

/** Approval rule model. @agent-model: order_approval_rules */
class ApprovalRuleModel extends Model
{
    protected $table = 'order_approval_rules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name','condition_type','threshold_amount','customer_id','custom_condition',
        'approver_ids','priority','is_active','created_at','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
