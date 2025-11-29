<?php

namespace App\Models;

use CodeIgniter\Model;

/** Campaign members schema. @agent-model: campaign_members */
class CampaignMemberModel extends Model
{
    protected $table = 'campaign_members';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'campaign_id',
        'lead_id',
        'customer_id',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
