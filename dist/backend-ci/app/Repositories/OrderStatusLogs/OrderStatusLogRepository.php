<?php

namespace App\Repositories\OrderStatusLogs;

use App\Models\OrderStatusLogModel;
use CodeIgniter\Database\BaseConnection;

/** Order status log persistence. @agent-repository: Order status logs @agent-pattern: Repository pattern @agent-reusable: HIGH */
class OrderStatusLogRepository
{
    protected OrderStatusLogModel $logs;
    protected BaseConnection $db;

    public function __construct(?OrderStatusLogModel $logs = null, ?BaseConnection $db = null)
    {
        $this->logs = $logs ?? new OrderStatusLogModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function create(int $orderId, ?string $from, string $to, ?int $userId = null, ?string $notes = null): array
    {
        $row = [
            'order_id' => $orderId,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by' => $userId,
            'notes' => $notes,
            'changed_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->logs->insert($row);
        $row['id'] = $this->logs->getInsertID();
        return $row;
    }

    public function byOrder(int $orderId): array
    {
        return $this->logs->where('order_id', $orderId)->orderBy('changed_at', 'ASC')->findAll();
    }
}
