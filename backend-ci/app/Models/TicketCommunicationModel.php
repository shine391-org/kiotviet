<?php

namespace App\Models;

use CodeIgniter\Model;

/** Ticket communication schema. @agent-model: ticket_communications */
class TicketCommunicationModel extends Model
{
    protected $table = 'ticket_communications';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'ticket_id',
        'type',
        'content',
        'attachments',
        'created_by',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
