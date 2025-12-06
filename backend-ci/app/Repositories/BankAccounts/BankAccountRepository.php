<?php

namespace App\Repositories\BankAccounts;

use App\Models\BankAccountModel;
use CodeIgniter\Database\BaseConnection;

/**
 * Bank Account persistence layer.
 *
 * @agent-repository: BankAccounts
 * @agent-pattern: Repository pattern
 */
class BankAccountRepository
{
    protected BankAccountModel $model;
    protected BaseConnection $db;

    public function __construct(?BankAccountModel $model = null, ?BaseConnection $db = null)
    {
        $this->model = $model ?? new BankAccountModel();
        $this->db = $db ?? \Config\Database::connect();
    }

    /** Find all bank accounts with filters */
    public function findAll(array $filters = []): array
    {
        $builder = $this->model->builder();

        if (isset($filters['is_active'])) {
            $builder->where('is_active', $filters['is_active']);
        } else {
            $builder->where('is_active', 1);
        }

        if (!empty($filters['branch_id'])) {
            $builder->groupStart()
                ->where('branch_id', $filters['branch_id'])
                ->orWhere('branch_id IS NULL')
                ->groupEnd();
        }

        if (!empty($filters['search'])) {
            $s = $filters['search'];
            $builder->groupStart()
                ->like('bank_name', $s)
                ->orLike('account_number', $s)
                ->orLike('account_name', $s)
                ->groupEnd();
        }

        $builder->orderBy('is_default', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('bank_name', 'ASC');

        $limit = $filters['limit'] ?? 50;
        $page = $filters['page'] ?? 1;
        $offset = ($page - 1) * $limit;

        return $builder->limit($limit, $offset)->get()->getResultArray();
    }

    /** Count bank accounts */
    public function count(array $filters = []): int
    {
        $builder = $this->model->builder();

        if (isset($filters['is_active'])) {
            $builder->where('is_active', $filters['is_active']);
        } else {
            $builder->where('is_active', 1);
        }

        if (!empty($filters['branch_id'])) {
            $builder->groupStart()
                ->where('branch_id', $filters['branch_id'])
                ->orWhere('branch_id IS NULL')
                ->groupEnd();
        }

        return $builder->countAllResults();
    }

    /** Find by ID */
    public function findById(int $id): ?array
    {
        return $this->model->find($id);
    }

    /** Create bank account */
    public function create(array $data): array
    {
        $now = date('Y-m-d H:i:s');
        $data['created_at'] = $now;
        $data['updated_at'] = $now;

        // If setting as default, unset others
        if (!empty($data['is_default'])) {
            $this->clearDefaults($data['branch_id'] ?? null);
        }

        $this->model->insert($data);
        $id = (int) $this->model->getInsertID();
        return $this->findById($id) ?? ($data + ['id' => $id]);
    }

    /** Update bank account */
    public function update(int $id, array $data): array
    {
        $data['updated_at'] = date('Y-m-d H:i:s');

        // If setting as default, unset others
        if (!empty($data['is_default'])) {
            $existing = $this->findById($id);
            $this->clearDefaults($existing['branch_id'] ?? null, $id);
        }

        $this->model->update($id, $data);
        return $this->findById($id);
    }

    /** Delete bank account (soft - set inactive) */
    public function delete(int $id): bool
    {
        return $this->model->update($id, [
            'is_active' => 0,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /** Clear default flag for branch */
    private function clearDefaults(?int $branchId, ?int $exceptId = null): void
    {
        $builder = $this->db->table('bank_accounts')
            ->set('is_default', 0);

        if ($branchId) {
            $builder->where('branch_id', $branchId);
        } else {
            $builder->where('branch_id IS NULL');
        }

        if ($exceptId) {
            $builder->where('id !=', $exceptId);
        }

        $builder->update();
    }
}
