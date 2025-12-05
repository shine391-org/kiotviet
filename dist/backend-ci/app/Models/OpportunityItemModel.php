<?php

namespace App\Models;

use CodeIgniter\Model;

/** Opportunity items schema. @agent-model: opportunity_items */
class OpportunityItemModel extends Model
{
    protected $table = 'opportunity_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'opportunity_id',
        'product_id',
        'quantity',
        'price',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
