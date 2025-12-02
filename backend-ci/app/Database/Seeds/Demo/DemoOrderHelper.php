<?php

namespace App\Database\Seeds\Demo;

use CodeIgniter\Database\BaseConnection;

/**
 * Shared helpers for demo order-based seeders.
 *
 * @agent-helper: Demo seed utilities
 */
class DemoOrderHelper
{
    public static function orders(BaseConnection $db, ?array $statuses = null): array
    {
        if (! $db->tableExists('orders')) {
            return [];
        }
        $builder = $db->table('orders')
            ->select('*')
            ->like('order_number', 'DH-DEMO-', 'after');
        if (! empty($statuses)) {
            $builder->whereIn('status', $statuses);
        }
        $rows = $builder->get()->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['order_number']] = $row + ['id' => (int) $row['id']];
        }
        return $map;
    }

    public static function orderItemsByOrder(BaseConnection $db, array $orderIds): array
    {
        if (empty($orderIds) || ! $db->tableExists('order_items')) {
            return [];
        }
        $rows = $db->table('order_items')
            ->whereIn('order_id', $orderIds)
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($rows as $row) {
            $orderId = (int) $row['order_id'];
            $row['id'] = (int) $row['id'];
            $map[$orderId][] = $row;
        }
        return $map;
    }

    public static function warehousesByBranch(BaseConnection $db): array
    {
        if (! $db->tableExists('warehouses')) {
            return [];
        }
        $query = $db->table('warehouses')->select('id, branch_id')->orderBy('id', 'ASC')->get();
        if ($query === false) {
            return [];
        }
        $rows = $query->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $branchId = (int) $row['branch_id'];
            if (! isset($map[$branchId])) {
                $map[$branchId] = (int) $row['id'];
            }
        }
        return $map;
    }
}
