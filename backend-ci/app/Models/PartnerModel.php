<?php

namespace App\Models;

use CodeIgniter\Model;

/** Partners schema model - for suppliers and vendors. @agent-model: partners */
class PartnerModel extends Model
{
    protected $table = 'partners';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'organization_id',
        'code',
        'name',
        'type',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'province',
        'district',
        'ward',
        'tax_code',
        'company_name',
        'group_name',
        'note',
        'credit_limit',
        'debt_amount',
        'total_purchased',
        'status',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
