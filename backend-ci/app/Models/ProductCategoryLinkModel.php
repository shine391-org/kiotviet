<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductCategoryLinkModel extends Model
{
    protected $table = 'product_category_links';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $allowedFields = ['product_id','category_id','created_at'];
    public $useTimestamps = false;
}
