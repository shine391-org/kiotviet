<?php

namespace App\Models;

use CodeIgniter\Model;

/** Appointment schema. @agent-model: appointments */
class AppointmentModel extends Model
{
    protected $table = 'appointments';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'customer_id',
        'lead_id',
        'contract_id',
        'start_time',
        'end_time',
        'status',
        'notes',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
