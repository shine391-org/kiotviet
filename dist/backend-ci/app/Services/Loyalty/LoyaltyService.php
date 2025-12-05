<?php

namespace App\Services\Loyalty;

use App\Repositories\Loyalty\LoyaltyRepository;
use RuntimeException;

/**
 * @agent-service: Loyalty points
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class LoyaltyService
{
    protected LoyaltyRepository $repo;

    public function __construct(?LoyaltyRepository $repo = null)
    {
        $this->repo = $repo ?? new LoyaltyRepository();
    }

    /**
     * Redeem points and return discount amount.
     *
     * @agent-use: POS checkout
     * @agent-pattern: Guard negative balance
     */
    public function redeem(int $customerId, int $points, ?int $orderId = null): array
    {
        $program = $this->repo->defaultProgram();
        if (! $program) {
            throw new RuntimeException('Loyalty program not configured');
        }
        if ($points <= 0) {
            return ['discount' => 0.0, 'points_used' => 0];
        }
        $wallet = $this->repo->walletForCustomer($customerId);
        if ($wallet['points_balance'] < $points) {
            throw new RuntimeException('Insufficient loyalty points');
        }
        $newBalance = $wallet['points_balance'] - $points;
        $discount = round($points * $program['redeem_rate'], 2);
        $this->repo->updateBalance($wallet['id'], $newBalance);
        $this->repo->addTransaction($wallet['id'], -$points, $orderId, 'redeem');

        return [
            'discount' => $discount,
            'points_used' => $points,
        ];
    }

    /**
     * Preview redemption without mutating state.
     */
    public function previewRedeem(int $customerId, int $points): array
    {
        $program = $this->repo->defaultProgram();
        if (! $program) {
            throw new RuntimeException('Loyalty program not configured');
        }
        if ($points <= 0) {
            return ['discount' => 0.0, 'points_used' => 0];
        }
        $wallet = $this->repo->walletForCustomer($customerId);
        if ($wallet['points_balance'] < $points) {
            throw new RuntimeException('Insufficient loyalty points');
        }
        $discount = round($points * $program['redeem_rate'], 2);
        return ['discount' => $discount, 'points_used' => $points];
    }

    /**
     * Earn points from amount and persist.
     */
    public function earn(int $customerId, float $amount, ?int $orderId = null): int
    {
        $program = $this->repo->defaultProgram();
        if (! $program || $amount <= 0) {
            return 0;
        }
        $points = (int) floor($amount * $program['earn_rate']);
        if ($points <= 0) {
            return 0;
        }
        $wallet = $this->repo->walletForCustomer($customerId);
        $newBalance = $wallet['points_balance'] + $points;
        $this->repo->updateBalance($wallet['id'], $newBalance);
        $this->repo->addTransaction($wallet['id'], $points, $orderId, 'earn');
        return $points;
    }
}
