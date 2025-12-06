<?php

namespace App\Models;

use CodeIgniter\Model;

class ShippingRateModel extends Model
{
    protected $table = 'shipping_rates';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'zone_id', 'delivery_partner_id', 'min_weight', 'max_weight',
        'min_value', 'max_value', 'base_fee', 'per_kg_fee',
        'free_shipping_threshold', 'is_active',
    ];
    protected $useTimestamps = true;
}
