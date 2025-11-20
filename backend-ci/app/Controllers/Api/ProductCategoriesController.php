<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductCategoryModel;
use CodeIgniter\API\ResponseTrait;

class ProductCategoriesController extends BaseController
{
    use ResponseTrait;

    protected ProductCategoryModel $categories;

    public function __construct()
    {
        $this->categories = new ProductCategoryModel();
    }

    public function index()
    {
        $data = $this->categories->where('deleted_at', null)->orderBy('sort_order', 'ASC')->findAll();
        return $this->respond([
            'success' => true,
            'data' => $data,
        ]);
    }
}
