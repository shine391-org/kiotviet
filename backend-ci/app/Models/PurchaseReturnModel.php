<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * PurchaseReturnModel - Model for purchase returns (Trả hàng nhập)
 * @agent-layer: backend-model
 */
class PurchaseReturnModel extends Model
{
    protected $table = 'purchase_returns';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'return_number',
        'purchase_order_id',
        'partner_id',
        'branch_id',
        'return_date',
        'total_quantity',
        'total_amount',
        'discount',
        'ncc_can_tra',
        'ncc_da_tra',
        'status',
        'notes',
        'created_by',
        'returned_by',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    protected $validationRules = [
        'return_number' => 'required|max_length[50]',
    ];

    protected $validationMessages = [];
    protected $skipValidation = false;
}
