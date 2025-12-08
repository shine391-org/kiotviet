<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stock Disposal model.
 * 
 * @agent-model: stock_disposals
 */
class StockDisposalModel extends Model
{
    protected $table = 'stock_disposals';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $allowedFields = [
        'code',
        'branch_id',
        'status',
        'disposed_at',
        'total_quantity',
        'total_value',
        'notes',
        'created_by',
        'executor_id',
    ];

    protected $validationRules = [
        'code' => 'required|max_length[50]',
        'branch_id' => 'required|integer',
        'status' => 'in_list[draft,completed,cancelled]',
    ];
}
