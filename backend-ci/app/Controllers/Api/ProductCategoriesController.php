<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ProductCategoryModel;
use CodeIgniter\API\ResponseTrait;

class ProductCategoriesController extends BaseController
{
    use ResponseTrait;

    protected ProductCategoryModel $categories;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->categories = new ProductCategoryModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // support ?view=tree
        $view = $this->request->getGet('view');
        if ($view === 'tree') {
            return $this->tree();
        }

        $data = $this->categories->where('deleted_at', null)->orderBy('sort_order', 'ASC')->findAll();
        return $this->respond(['success' => true, 'data' => $data]);
    }

    protected function tree()
    {
        $rows = $this->categories->where('deleted_at', null)
            ->orderBy('parent_id')->orderBy('sort_order')->findAll();
        $countMap = $this->getProductCountMap();

        $byId = [];
        foreach ($rows as $row) {
            $row['children'] = [];
            $row['product_count'] = $countMap[$row['id']] ?? 0;
            $byId[$row['id']] = $row;
        }
        $tree = [];
        foreach ($byId as $id => &$cat) {
            if (!empty($cat['parent_id']) && isset($byId[$cat['parent_id']])) {
                $byId[$cat['parent_id']]['children'][] = &$cat;
            } else {
                $tree[] = &$cat;
            }
        }

        return $this->respond(['success' => true, 'data' => $tree]);
    }

    protected function getProductCountMap(): array
    {
        $res = $this->db->table('product_category_links')
            ->select('category_id, COUNT(*) as cnt')
            ->groupBy('category_id')
            ->get()->getResultArray();
        $map = [];
        foreach ($res as $row) {
            $map[$row['category_id']] = (int) $row['cnt'];
        }
        return $map;
    }
}
