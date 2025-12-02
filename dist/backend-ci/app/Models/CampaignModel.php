<?php

namespace App\Models;

use CodeIgniter\Model;

/** Campaign schema. @agent-model: campaigns */
class CampaignModel extends Model
{
    protected $table = 'campaigns';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'name',
        'status',
        'source',
        'budget',
        'start_date',
        'end_date',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
