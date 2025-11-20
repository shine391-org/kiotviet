<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class ProductMediaController extends BaseController
{
    use ResponseTrait;

    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function library()
    {
        $limit = (int) ($this->request->getGet('limit') ?? 20);
        $offset = (int) ($this->request->getGet('offset') ?? 0);
        $entityId = $this->request->getGet('entity_id');

        $builder = $this->db->table('product_images')->where('deleted_at', null);
        if ($entityId) {
            $builder->groupStart()
                ->where('variant_id', $entityId)
                ->orWhere('product_id', $entityId)
                ->groupEnd();
        }
        $total = $builder->countAllResults(false);
        $data = $builder->orderBy('created_at', 'DESC')->limit($limit, $offset)->get()->getResultArray();

        return $this->respond([
            'success' => true,
            'data' => $data,
            'total' => $total,
        ]);
    }

    public function byDate()
    {
        $year = $this->request->getGet('year');
        $month = $this->request->getGet('month');
        $builder = $this->db->table('product_images')->where('deleted_at', null);
        if ($year) {
            $builder->where('YEAR(created_at)', $year);
        }
        if ($month) {
            $builder->where('MONTH(created_at)', $month);
        }
        $data = $builder->orderBy('created_at', 'DESC')->get()->getResultArray();
        return $this->respond(['success' => true, 'data' => $data]);
    }

    public function searchSku()
    {
        $sku = $this->request->getGet('sku');
        $limit = (int) ($this->request->getGet('limit') ?? 20);
        if (!$sku || strlen($sku) < 2) {
            return $this->respond(['success' => true, 'data' => [], 'total' => 0]);
        }

        $builder = $this->db->table('product_images pi')
            ->select('pi.*')
            ->join('product_variants_v2 v', 'v.id = pi.variant_id', 'left')
            ->join('products p', 'p.id = pi.product_id', 'left')
            ->groupStart()
                ->like('v.sku', $sku)
                ->orLike('p.code', $sku)
            ->groupEnd()
            ->where('pi.deleted_at', null)
            ->orderBy('pi.created_at', 'DESC')
            ->limit($limit);
        $data = $builder->get()->getResultArray();
        return $this->respond(['success' => true, 'data' => $data, 'total' => count($data)]);
    }
}
