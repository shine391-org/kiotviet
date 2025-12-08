<?php

namespace App\Models;

use CodeIgniter\Model;

class StockTransferItemModel extends Model
{
    protected $table = 'stock_transfer_items';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'transfer_id',
        'product_id',
        'variant_id',
        'product_code',
        'product_name',
        'unit',
        'quantity_sent',
        'quantity_received',
        'unit_price',
        'total_price',
        'notes',
        'created_at',
        'updated_at',
    ];
}
