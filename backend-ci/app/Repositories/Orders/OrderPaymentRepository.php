<?php

namespace App\Repositories\Orders;

use CodeIgniter\Database\BaseConnection;

class OrderPaymentRepository
{
    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? \Config\Database::connect();
    }

    public function create(array $data): int
    {
        $payload = $data + [
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->table('order_payments')->insert($payload);
        return (int) $this->db->insertID();
    }

    public function sumByOrder(int $orderId): float
    {
        $row = $this->db->table('order_payments')
            ->selectSum('amount', 'total')
            ->where('order_id', $orderId)
            ->get()->getRow();
        return (float) ($row->total ?? 0);
    }

    public function findByOrder(int $orderId): array
    {
        return $this->db->table('order_payments')
            ->where('order_id', $orderId)
            ->get()->getResultArray();
    }
}
