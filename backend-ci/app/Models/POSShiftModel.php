<?php

namespace App\Models;

use CodeIgniter\Model;

/** POS shift schema. @agent-model: pos_shifts */
class POSShiftModel extends Model
{
    protected $table = 'pos_shifts';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'user_id',
        'profile_id',
        'branch_id',
        'opening_balance',
        'expected_total',
        'expected_cash',
        'expected_card',
        'actual_total',
        'actual_cash',
        'actual_card',
        'discrepancy',
        'status',
        'opened_at',
        'closed_at',
        'closing_note',
        'created_at',
        'updated_at',
    ];
}
