<?php

namespace App\Models;

use CodeIgniter\Model;

/** Withholding rule schema. @agent-model: withholding_rules */
class WithholdingRuleModel extends Model
{
    protected $table = 'withholding_rules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'rate_percent',
        'apply_threshold',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
