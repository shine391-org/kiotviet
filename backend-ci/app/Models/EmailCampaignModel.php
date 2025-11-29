<?php

namespace App\Models;

use CodeIgniter\Model;

/** Email campaign schema. @agent-model: email_campaigns */
class EmailCampaignModel extends Model
{
    protected $table = 'email_campaigns';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'campaign_id',
        'subject',
        'template',
        'schedule_at',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
