<?php

namespace App\Repositories\Loyalty;

use App\Models\LoyaltyWalletModel;
use CodeIgniter\Database\BaseConnection;

class LoyaltyRepository
{
    protected LoyaltyWalletModel $walletModel;
    protected BaseConnection $db;

    public function __construct()
    {
        $this->walletModel = new LoyaltyWalletModel();
        $this->db = \Config\Database::connect();
    }

    public function findWalletByCustomer(int $customerId): ?array
    {
        return $this->walletModel->where('customer_id', $customerId)->first();
    }

    public function getOrCreateWallet(int $customerId): array
    {
        $wallet = $this->findWalletByCustomer($customerId);
        if ($wallet) {
            return $wallet;
        }

        $this->walletModel->insert([
            'customer_id' => $customerId,
            'points_balance' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->walletModel->find((int) $this->walletModel->getInsertID());
    }

    public function getActiveProgram(?int $customerGroupId = null): ?array
    {
        $builder = $this->db->table('loyalty_programs')->where('status', 'active');
        if ($customerGroupId) {
            $builder->groupStart()->where('customer_group_id', $customerGroupId)->orWhere('customer_group_id IS NULL')->groupEnd();
        }
        return $builder->orderBy('customer_group_id', 'DESC')->get()->getRowArray();
    }

    public function addPoints(int $walletId, float $points, ?int $orderId, string $reason): void
    {
        $this->db->transStart();

        // Update wallet balance
        $this->db->table('loyalty_wallets')
            ->where('id', $walletId)
            ->set('points_balance', 'points_balance + ' . $points, false)
            ->set('last_earned_at', date('Y-m-d H:i:s'))
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->update();

        // Record transaction
        $this->db->table('loyalty_transactions')->insert([
            'wallet_id' => $walletId,
            'order_id' => $orderId,
            'points_delta' => $points,
            'reason' => $reason,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();
    }

    public function redeemPoints(int $walletId, float $points, ?int $orderId, string $reason): bool
    {
        $wallet = $this->walletModel->find($walletId);
        if (!$wallet || $wallet['points_balance'] < $points) {
            return false;
        }

        $this->db->transStart();

        // Deduct points
        $this->db->table('loyalty_wallets')
            ->where('id', $walletId)
            ->set('points_balance', 'points_balance - ' . $points, false)
            ->set('updated_at', date('Y-m-d H:i:s'))
            ->update();

        // Record transaction (negative)
        $this->db->table('loyalty_transactions')->insert([
            'wallet_id' => $walletId,
            'order_id' => $orderId,
            'points_delta' => -$points,
            'reason' => $reason,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $this->db->transComplete();
        return $this->db->transStatus();
    }

    public function getTransactions(int $walletId, int $limit = 50): array
    {
        return $this->db->table('loyalty_transactions')
            ->where('wallet_id', $walletId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get()
            ->getResultArray();
    }

    public function getCustomerInfo(int $customerId): ?array
    {
        return $this->db->table('customers')
            ->select('id, name, phone, email, customer_group_id')
            ->where('id', $customerId)
            ->get()
            ->getRowArray();
    }
}
