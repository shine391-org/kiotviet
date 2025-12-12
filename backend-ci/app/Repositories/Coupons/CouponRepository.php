<?php

namespace App\Repositories\Coupons;

use App\Models\CouponModel;
use CodeIgniter\Database\BaseConnection;

class CouponRepository
{
    protected CouponModel $model;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->model = new CouponModel();
        $this->db = \Config\Database::connect();
    }

    public function findAll(array $filters = []): array
    {
        $builder = $this->model->builder();

        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        if (!empty($filters['branch_id'])) {
            $builder->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('code', $filters['search'])
                ->orLike('name', $filters['search'])
                ->groupEnd();
        }

        $limit = $filters['limit'] ?? 50;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        return $builder->orderBy('created_at', 'DESC')->limit($limit, $offset)->get()->getResultArray();
    }

    public function count(array $filters = []): int
    {
        $builder = $this->model->builder();
        if (!empty($filters['status'])) {
            $builder->where('status', $filters['status']);
        }
        if (!empty($filters['branch_id'])) {
            $builder->where('branch_id', $filters['branch_id']);
        }
        if (!empty($filters['search'])) {
            $builder->groupStart()
                ->like('code', $filters['search'])
                ->orLike('name', $filters['search'])
                ->groupEnd();
        }
        return $builder->countAllResults();
    }

    public function findById(int $id): ?array
    {
        return $this->model->find($id);
    }

    public function findByCode(string $code): ?array
    {
        return $this->model->where('code', $code)->first();
    }

    public function create(array $data): ?array
    {
        $this->model->insert($data);
        return $this->findById((int) $this->model->getInsertID());
    }

    public function update(int $id, array $data): ?array
    {
        $this->model->update($id, $data);
        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        return $this->model->delete($id);
    }

    public function incrementUsage(int $id): void
    {
        $this->db->table('coupons')->where('id', $id)->set('used_count', 'used_count + 1', false)->update();
    }

    public function recordUsage(int $couponId, ?int $orderId, ?int $customerId): void
    {
        $this->db->table('coupon_usages')->insert([
            'coupon_id' => $couponId,
            'order_id' => $orderId,
            'customer_id' => $customerId,
            'used_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getUsageCount(int $couponId, ?int $customerId = null): int
    {
        $builder = $this->db->table('coupon_usages')->where('coupon_id', $couponId);
        if ($customerId) {
            $builder->where('customer_id', $customerId);
        }
        return $builder->countAllResults();
    }
}
