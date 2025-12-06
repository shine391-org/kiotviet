<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * SupplierDebtTransactionModel - Tracks supplier debt adjustments, payments, discounts
 * @agent-model: supplier_debt_transactions
 */
class SupplierDebtTransactionModel extends Model
{
    protected $table = 'supplier_debt_transactions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'partner_id',
        'type',
        'amount',
        'debt_before',
        'debt_after',
        'payment_method',
        'executor_id',
        'note',
        'reference_type',
        'reference_id',
        'transaction_date',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get transactions by partner
     */
    public function getByPartnerId(int $partnerId, int $limit = 50): array
    {
        return $this->where('partner_id', $partnerId)
            ->orderBy('transaction_date', 'DESC')
            ->limit($limit)
            ->findAll();
    }
}
