<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'db_products';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'product_type','code','barcode','name','slug','brand','unit',
        'purchase_price','selling_price','wholesale_price','stock_quantity',
        'alert_stock','has_variants','image','images','weight','dimensions',
        'description','content','is_active','is_available_online','is_featured',
        'status','meta_title','meta_description','meta_keywords',
        'created_at','updated_at','deleted_at'
    ];
    protected $useTimestamps = false;
}
