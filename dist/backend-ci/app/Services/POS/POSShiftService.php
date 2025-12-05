<?php

namespace App\Services\POS;

use App\Repositories\POS\POSShiftRepository;
use App\Validators\POSShiftValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-service: POS shift lifecycle
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class POSShiftService
{
    protected POSShiftRepository $repo;
    protected POSShiftValidator $validator;

    public function __construct(?POSShiftRepository $repo = null, ?POSShiftValidator $validator = null)
    {
        $this->repo = $repo ?? new POSShiftRepository();
        $this->validator = $validator ?? new POSShiftValidator();
    }

    /**
     * Open a new shift (enforce single open per user).
     *
     * @agent-use: POST /api/pos/shifts/open
     * @agent-pattern: Guard + create
     */
    public function open(array $input): array
    {
        $payload = $this->validator->validateOpen($input);
        $existing = $this->repo->findOpenByUser($payload['user_id'], $payload['branch_id']);
        if ($existing) {
            throw new InvalidArgumentException('User already has an open shift');
        }
        $shift = $this->repo->create($payload);
        return ['success' => true, 'data' => $shift];
    }

    /**
     * Ensure there is an open shift for user when required.
     *
     * @agent-use: POS checkout guard
     * @agent-pattern: Guard + fetch
     */
    public function requireOpenShift(int $userId, ?int $branchId = null): array
    {
        $shift = $this->repo->findOpenByUser($userId, $branchId);
        if (! $shift) {
            throw new RuntimeException('No open shift for user');
        }
        return $shift;
    }

    /**
     * Log payment towards expected totals.
     */
    public function recordPayment(int $shiftId, array $payment): void
    {
        $data = $this->validator->validatePayment($payment + ['shift_id' => $shiftId]);
        $this->repo->logPayment($shiftId, $data);
        $totals = $this->repo->paymentsByMethod($shiftId);
        $expectedTotal = array_sum($totals);
        $expectedCash = $this->cashTotal($totals);
        $expectedCard = $expectedTotal - $expectedCash;
        $this->repo->updateShift($shiftId, [
            'expected_total' => $expectedTotal,
            'expected_cash' => $expectedCash,
            'expected_card' => $expectedCard,
        ]);
    }

    /**
     * Close shift and reconcile discrepancy.
     *
     * @agent-use: POST /api/pos/shifts/close
     * @agent-pattern: Reconcile expected vs actual
     */
    public function close(array $input): array
    {
        $payload = $this->validator->validateClose($input);
        $shift = $this->repo->findById($payload['shift_id']);
        if (! $shift || ($shift['status'] ?? '') !== 'open') {
            throw new RuntimeException('Shift not open or not found');
        }

        $expectedTotals = $this->repo->paymentsByMethod($shift['id']);
        $expectedTotal = array_sum($expectedTotals);
        $expectedCash = $this->cashTotal($expectedTotals);
        $expectedCard = $expectedTotal - $expectedCash;

        $actualCash = $this->cashTotal($payload['actual_payments']);
        $actualTotal = array_sum($payload['actual_payments']);
        $actualCard = $actualTotal - $actualCash;
        $discrepancy = round($actualTotal - $expectedTotal, 2);

        foreach ($payload['actual_payments'] as $method => $amount) {
            $this->repo->logPayment($shift['id'], [
                'payment_method' => $method,
                'amount' => $amount,
                'reference_type' => 'closing',
                'reference_id' => $shift['id'],
            ]);
        }

        $this->repo->close($shift['id'], [
            'expected_total' => $expectedTotal,
            'expected_cash' => $expectedCash,
            'expected_card' => $expectedCard,
            'actual_total' => $actualTotal,
            'actual_cash' => $actualCash,
            'actual_card' => $actualCard,
            'discrepancy' => $discrepancy,
            'closing_note' => $payload['closing_note'] ?? null,
        ]);

        return [
            'success' => true,
            'data' => [
                'id' => $shift['id'],
                'expected_total' => $expectedTotal,
                'actual_total' => $actualTotal,
                'discrepancy' => $discrepancy,
            ],
        ];
    }

    private function cashTotal(array $totals): float
    {
        $cash = 0.0;
        foreach ($totals as $method => $amount) {
            $code = strtoupper(is_string($method) ? $method : (string) $method);
            if ($this->isCashMethod($code)) {
                $cash += (float) $amount;
            }
        }
        return $cash;
    }

    private function isCashMethod(string $method): bool
    {
        return in_array(strtoupper($method), ['CASH', 'COD'], true);
    }
}
