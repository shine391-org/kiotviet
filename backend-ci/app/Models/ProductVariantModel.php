<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductVariantModel extends Model
{
    protected $table = 'product_variants';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'product_id','code','name','price','stock_quantity','attributes','image',
        'created_at','updated_at','deleted_at'
    ];
    protected $useTimestamps = false;
}
