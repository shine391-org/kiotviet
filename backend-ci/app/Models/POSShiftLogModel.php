<?php

namespace App\Models;

use CodeIgniter\Model;

/** POS shift action log. @agent-model: pos_shift_logs */
class POSShiftLogModel extends Model
{
    protected $table = 'pos_shift_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'shift_id',
        'action',
        'message',
        'created_at',
        'updated_at',
    ];
}
