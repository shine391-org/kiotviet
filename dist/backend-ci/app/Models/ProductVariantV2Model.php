<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductVariantV2Model extends Model
{
    protected $table = 'product_variants_v2';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'product_id','variant_name','variant_signature','sku','barcode',
        'price','cost_price','stock_quantity','min_stock','max_stock','image_url',
        'attributes','status','created_at','updated_at','deleted_at'
    ];
    protected $useTimestamps = false;
}
