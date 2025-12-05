<?php

namespace App\Models;

use CodeIgniter\Model;

/** Payment schedule schema. @agent-model: payment_schedules */
class PaymentScheduleModel extends Model
{
    protected $table = 'payment_schedules';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'invoice_id',
        'due_date',
        'amount',
        'status',
        'created_at',
        'updated_at',
    ];
    protected $useSoftDeletes = false;
}
