<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductModel;
use App\Models\ProductCategoryLinkModel;
use CodeIgniter\API\ResponseTrait;

class ProductsController extends BaseController
{
    use ResponseTrait;

    protected ProductModel $products;
    protected ProductCategoryLinkModel $links;

    public function __construct()
    {
        $this->products = new ProductModel();
        $this->links    = new ProductCategoryLinkModel();
    }

    /**
    * GET /api/products
    */
    public function index()
    {
        $page  = max(1, (int) $this->request->getGet('page'));
        $limit = max(1, (int) ($this->request->getGet('limit') ?? 20));
        $offset= ($page - 1) * $limit;

        $builder = $this->products->where('deleted_at', null);

        // search by code or name
        if ($search = $this->request->getGet('search')) {
            $builder->like('code', $search)
                    ->orLike('name', $search)
                    ->orLike('barcode', $search);
        }

        // status filter
        if ($status = $this->request->getGet('status')) {
            $builder->where('status', $status);
        }

        // product_type filter
        if ($ptype = $this->request->getGet('product_type')) {
            $builder->where('product_type', $ptype);
        }

        $total = $builder->countAllResults(false);
        $data  = $builder->orderBy('created_at', 'DESC')
                         ->limit($limit, $offset)
                         ->find();

        // attach category ids
        $ids = array_column($data, 'id');
        if (!empty($ids)) {
            $linkRows = $this->links->select('product_id, category_id')
                                    ->whereIn('product_id', $ids)
                                    ->findAll();
            $map = [];
            foreach ($linkRows as $row) {
                $map[$row['product_id']][] = (int) $row['category_id'];
            }
            foreach ($data as &$row) {
                $row['category_ids'] = $map[$row['id']] ?? [];
                $row['variants'] = []; // placeholder for FE
            }
        }

        return $this->respond([
            'success' => true,
            'data' => $data,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => (int) ceil($total / $limit),
            ],
        ]);
    }

    /**
    * GET /api/products/{id}
    */
    public function show($id = null)
    {
        $product = $this->products->find($id);
        if (!$product) {
            return $this->failNotFound('Product not found');
        }

        $linkRows = $this->links->select('category_id')->where('product_id', $id)->findAll();
        $product['category_ids'] = array_map(fn($r) => (int) $r['category_id'], $linkRows);
        $product['variants'] = []; // placeholder

        return $this->respond([
            'success' => true,
            'data' => $product,
        ]);
    }
}
