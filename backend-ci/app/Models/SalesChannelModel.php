<?php

namespace App\Models;

use CodeIgniter\Model;

class SalesChannelModel extends Model
{
    protected $table = 'sales_channels';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'code', 'name', 'description', 'icon', 'color',
        'is_active', 'is_default', 'sort_order', 'settings',
    ];
    protected $useTimestamps = true;
}
