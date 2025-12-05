<?php

namespace App\Models;

use CodeIgniter\Model;

/** Support ticket schema. @agent-model: support_tickets */
class SupportTicketModel extends Model
{
    protected $table = 'support_tickets';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'subject',
        'customer_id',
        'lead_id',
        'priority',
        'status',
        'assigned_to',
        'description',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
