<?php

namespace App\Models;

use CodeIgniter\Model;

/** Ticket event schema. @agent-model: ticket_events */
class TicketEventModel extends Model
{
    protected $table = 'ticket_events';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'ticket_id',
        'event_type',
        'from_status',
        'to_status',
        'description',
        'created_at',
    ];
    protected $useSoftDeletes = false;
}
