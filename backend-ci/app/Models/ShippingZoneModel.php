<?php

namespace App\Models;

use CodeIgniter\Model;

class ShippingZoneModel extends Model
{
    protected $table = 'shipping_zones';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'name', 'province_ids', 'district_ids', 'is_active', 'sort_order',
    ];
    protected $useTimestamps = true;
}
