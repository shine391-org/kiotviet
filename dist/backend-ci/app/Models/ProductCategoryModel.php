<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductCategoryModel extends Model
{
    protected $table = 'product_categories';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'parent_id','level','code','name','slug','description','image',
        'sort_order','status','created_at','updated_at','deleted_at'
    ];
    protected $useTimestamps = false;
}
