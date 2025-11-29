<?php

namespace App\Repositories\Loyalty;

use App\Models\LoyaltyProgramModel;
use App\Models\LoyaltyTransactionModel;
use App\Models\LoyaltyWalletModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Loyalty
 * @agent-pattern: Repository pattern
 * @agent-reusable: MEDIUM
 */
class LoyaltyRepository
{
    protected BaseConnection $db;

    public function __construct(
        ?LoyaltyProgramModel $programs = null,
        ?LoyaltyWalletModel $wallets = null,
        ?LoyaltyTransactionModel $transactions = null,
        ?BaseConnection $db = null
    ) {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
    }

    public function defaultProgram(): ?array
    {
        $row = $this->db->table('loyalty_programs')->where('status', 'active')->orderBy('id', 'ASC')->get()->getRowArray();
        return $row ? $this->hydrateProgram($row) : null;
    }

    public function walletForCustomer(int $customerId): array
    {
        $existing = $this->db->table('loyalty_wallets')->where('customer_id', $customerId)->get()->getRowArray();
        if ($existing) {
            return $this->hydrateWallet($existing);
        }
        $now = $this->now();
        $this->db->table('loyalty_wallets')->insert([
            'customer_id' => $customerId,
            'points_balance' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $id = (int) $this->db->insertID();
        $row = $this->db->table('loyalty_wallets')->where('id', $id)->get()->getRowArray();
        return $this->hydrateWallet($row ?: ['id' => $id, 'customer_id' => $customerId, 'points_balance' => 0]);
    }

    public function updateBalance(int $walletId, float $newBalance): void
    {
        $this->db->table('loyalty_wallets')->where('id', $walletId)->update([
            'points_balance' => $newBalance,
            'updated_at' => $this->now(),
            'last_earned_at' => $this->now(),
        ]);
    }

    public function addTransaction(int $walletId, float $pointsDelta, ?int $orderId, string $reason): void
    {
        $this->db->table('loyalty_transactions')->insert([
            'wallet_id' => $walletId,
            'order_id' => $orderId,
            'points_delta' => $pointsDelta,
            'reason' => $reason,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ]);
    }

    private function hydrateWallet(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['customer_id'] = isset($row['customer_id']) ? (int) $row['customer_id'] : null;
        $row['points_balance'] = isset($row['points_balance']) ? (float) $row['points_balance'] : 0.0;
        return $row;
    }

    private function hydrateProgram(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['customer_group_id'] = isset($row['customer_group_id']) ? (int) $row['customer_group_id'] : null;
        $row['earn_rate'] = isset($row['earn_rate']) ? (float) $row['earn_rate'] : 0.0;
        $row['redeem_rate'] = isset($row['redeem_rate']) ? (float) $row['redeem_rate'] : 0.0;
        $row['expiry_days'] = isset($row['expiry_days']) ? (int) $row['expiry_days'] : 0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
