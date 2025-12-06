<?php

namespace App\Services\Loyalty;

use App\Repositories\Loyalty\LoyaltyRepository;
use InvalidArgumentException;
use RuntimeException;

class LoyaltyService
{
    protected LoyaltyRepository $repo;

    public function __construct(?LoyaltyRepository $repo = null)
    {
        $this->repo = $repo ?? new LoyaltyRepository();
    }

    /** Get customer wallet and loyalty info */
    public function getWallet(int $customerId): array
    {
        $customer = $this->repo->getCustomerInfo($customerId);
        if (!$customer) {
            throw new RuntimeException('Customer not found');
        }

        $wallet = $this->repo->getOrCreateWallet($customerId);
        $program = $this->repo->getActiveProgram($customer['customer_group_id'] ?? null);

        return [
            'success' => true,
            'data' => [
                'customer_id' => $customerId,
                'customer_name' => $customer['name'],
                'points_balance' => (float) $wallet['points_balance'],
                'wallet_id' => (int) $wallet['id'],
                'program' => $program ? [
                    'id' => (int) $program['id'],
                    'name' => $program['name'],
                    'earn_rate' => (float) $program['earn_rate'],
                    'redeem_rate' => (float) $program['redeem_rate'],
                    'expiry_days' => (int) $program['expiry_days'],
                ] : null,
            ],
        ];
    }

    /** Calculate points to earn for an order */
    public function calculateEarnPoints(int $customerId, float $orderTotal): array
    {
        $customer = $this->repo->getCustomerInfo($customerId);
        $program = $this->repo->getActiveProgram($customer['customer_group_id'] ?? null);

        if (!$program) {
            return [
                'success' => true,
                'data' => ['points_to_earn' => 0, 'message' => 'No active loyalty program'],
            ];
        }

        $earnRate = (float) $program['earn_rate'];
        $pointsToEarn = floor($orderTotal * $earnRate);

        return [
            'success' => true,
            'data' => [
                'points_to_earn' => $pointsToEarn,
                'earn_rate' => $earnRate,
                'order_total' => $orderTotal,
                'program_name' => $program['name'],
            ],
        ];
    }

    /** Preview redeem points */
    public function redeemPreview(int $customerId, float $pointsToRedeem): array
    {
        $customer = $this->repo->getCustomerInfo($customerId);
        if (!$customer) {
            throw new RuntimeException('Customer not found');
        }

        $wallet = $this->repo->findWalletByCustomer($customerId);
        if (!$wallet) {
            throw new InvalidArgumentException('Customer has no loyalty wallet');
        }

        $balance = (float) $wallet['points_balance'];
        if ($pointsToRedeem > $balance) {
            throw new InvalidArgumentException('Insufficient points. Available: ' . $balance);
        }

        $program = $this->repo->getActiveProgram($customer['customer_group_id'] ?? null);
        if (!$program) {
            throw new InvalidArgumentException('No active loyalty program');
        }

        $redeemRate = (float) $program['redeem_rate'];
        $discountAmount = $pointsToRedeem * $redeemRate;

        return [
            'success' => true,
            'data' => [
                'points_to_redeem' => $pointsToRedeem,
                'discount_amount' => round($discountAmount, 0),
                'redeem_rate' => $redeemRate,
                'points_balance' => $balance,
                'remaining_points' => $balance - $pointsToRedeem,
            ],
        ];
    }

    /** Earn points after order completion */
    public function earnPoints(int $customerId, float $orderTotal, ?int $orderId = null): array
    {
        $customer = $this->repo->getCustomerInfo($customerId);
        $program = $this->repo->getActiveProgram($customer['customer_group_id'] ?? null);

        if (!$program) {
            return ['success' => true, 'data' => ['points_earned' => 0]];
        }

        $wallet = $this->repo->getOrCreateWallet($customerId);
        $earnRate = (float) $program['earn_rate'];
        $pointsToEarn = floor($orderTotal * $earnRate);

        if ($pointsToEarn > 0) {
            $this->repo->addPoints((int) $wallet['id'], $pointsToEarn, $orderId, 'Earned from order');
        }

        return [
            'success' => true,
            'data' => [
                'points_earned' => $pointsToEarn,
                'new_balance' => (float) $wallet['points_balance'] + $pointsToEarn,
            ],
        ];
    }

    /** Redeem points for order discount */
    public function redeemPoints(int $customerId, float $pointsToRedeem, ?int $orderId = null): array
    {
        $customer = $this->repo->getCustomerInfo($customerId);
        if (!$customer) {
            throw new RuntimeException('Customer not found');
        }

        $wallet = $this->repo->findWalletByCustomer($customerId);
        if (!$wallet || $wallet['points_balance'] < $pointsToRedeem) {
            throw new InvalidArgumentException('Insufficient points');
        }

        $program = $this->repo->getActiveProgram($customer['customer_group_id'] ?? null);
        if (!$program) {
            throw new InvalidArgumentException('No active loyalty program');
        }

        $redeemRate = (float) $program['redeem_rate'];
        $discountAmount = $pointsToRedeem * $redeemRate;

        $success = $this->repo->redeemPoints((int) $wallet['id'], $pointsToRedeem, $orderId, 'Redeemed for order');
        if (!$success) {
            throw new RuntimeException('Failed to redeem points');
        }

        return [
            'success' => true,
            'data' => [
                'points_redeemed' => $pointsToRedeem,
                'discount_amount' => round($discountAmount, 0),
                'new_balance' => (float) $wallet['points_balance'] - $pointsToRedeem,
            ],
        ];
    }

    /** Get transaction history */
    public function getTransactions(int $customerId): array
    {
        $wallet = $this->repo->findWalletByCustomer($customerId);
        if (!$wallet) {
            return ['success' => true, 'data' => []];
        }

        $transactions = $this->repo->getTransactions((int) $wallet['id']);
        return [
            'success' => true,
            'data' => array_map(fn($t) => [
                'id' => (int) $t['id'],
                'points_delta' => (float) $t['points_delta'],
                'reason' => $t['reason'],
                'order_id' => $t['order_id'] ? (int) $t['order_id'] : null,
                'created_at' => $t['created_at'],
            ], $transactions),
        ];
    }
}
