<?php

namespace App\Models;

use CodeIgniter\Model;

/** Email campaign logs. @agent-model: email_campaign_logs */
class EmailCampaignLogModel extends Model
{
    protected $table = 'email_campaign_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'email_campaign_id',
        'member_id',
        'status',
        'message',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
