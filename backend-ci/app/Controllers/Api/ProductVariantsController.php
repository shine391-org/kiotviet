<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductVariantV2Model;
use CodeIgniter\API\ResponseTrait;

class ProductVariantsController extends BaseController
{
    use ResponseTrait;

    protected ProductVariantV2Model $variants;

    public function __construct()
    {
        $this->variants = new ProductVariantV2Model();
    }

    public function show($id)
    {
        $variant = $this->variants->find($id);
        if (!$variant) {
            return $this->failNotFound('Variant not found');
        }
        return $this->respond(['success' => true, 'data' => $variant]);
    }
}
