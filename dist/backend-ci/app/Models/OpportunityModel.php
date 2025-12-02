<?php

namespace App\Models;

use CodeIgniter\Model;

/** Opportunity schema. @agent-model: opportunities */
class OpportunityModel extends Model
{
    protected $table = 'opportunities';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'lead_id',
        'customer_id',
        'title',
        'stage',
        'probability',
        'expected_value',
        'closing_date',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
