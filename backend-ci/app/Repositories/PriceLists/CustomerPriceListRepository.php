<?php

namespace App\Repositories\PriceLists;

use App\Models\CustomerPriceListModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Customer price list mapping repository.
 *
 * @agent-repository: Customer price lists
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class CustomerPriceListRepository
{
    protected CustomerPriceListModel $model;
    protected BaseConnection $db;

    public function __construct(?CustomerPriceListModel $model = null, ?BaseConnection $db = null)
    {
        $this->model = $model ?? new CustomerPriceListModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    public function create(array $data): array
    {
        $payload = $data + ['created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
        $this->model->insert($payload);
        $payload['id'] = (int) $this->model->getInsertID();
        return $payload;
    }

    public function update(int $id, array $data): bool
    {
        return (bool) $this->model->update($id, $data + ['updated_at' => date('Y-m-d H:i:s')]);
    }

    public function activeForCustomer(int $customerId, string $date): ?array
    {
        $row = $this->db->table('customer_price_lists')
            ->where('customer_id', $customerId)
            ->where('is_active', 1)
            ->groupStart()
                ->where('valid_from IS NULL', null, false)
                ->orWhere('valid_from <=', $date)
            ->groupEnd()
            ->groupStart()
                ->where('valid_to IS NULL', null, false)
                ->orWhere('valid_to >=', $date)
            ->groupEnd()
            ->orderBy('id', 'DESC')
            ->get()->getRowArray();
        return $row ?: null;
    }
}
