<?php

namespace App\Models;

use CodeIgniter\Model;

/** Pricing rule model. @agent-model: pricing_rules */
class PricingRuleModel extends Model
{
    protected $table = 'pricing_rules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name','condition_type','customer_id','project_id','product_id','variant_id',
        'min_qty','start_date','end_date','price','discount_percent','priority','is_active',
        'created_at','updated_at',
    ];
    protected $useSoftDeletes = false;
    protected $useTimestamps = false;
}
