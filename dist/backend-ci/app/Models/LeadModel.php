<?php

namespace App\Models;

use CodeIgniter\Model;

/** Lead schema. @agent-model: leads */
class LeadModel extends Model
{
    protected $table = 'leads';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'lead_number',
        'name',
        'email',
        'phone',
        'source',
        'status',
        'company',
        'notes',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
