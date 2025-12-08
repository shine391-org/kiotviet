<?php

namespace App\Models;

use CodeIgniter\Model;

class StockTransferModel extends Model
{
    protected $table = 'stock_transfers';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'code',
        'status',
        'from_branch_id',
        'to_branch_id',
        'transfer_date',
        'receive_date',
        'notes',
        'receiving_notes',
        'total_items',
        'quantity_sent',
        'value_sent',
        'quantity_received',
        'value_received',
        'created_by',
        'received_by',
        'created_at',
        'updated_at',
    ];
}
