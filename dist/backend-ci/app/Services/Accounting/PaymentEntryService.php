<?php

namespace App\Services\Accounting;

use App\Repositories\Accounting\PaymentEntryRepository;
use App\Validators\PaymentEntryValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-service: Payment entry (accounting)
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class PaymentEntryService
{
    protected PaymentEntryRepository $repo;
    protected PaymentEntryValidator $validator;
    protected AccountingService $accounting;

    public function __construct(
        ?PaymentEntryRepository $repo = null,
        ?PaymentEntryValidator $validator = null,
        ?AccountingService $accounting = null
    ) {
        $this->repo = $repo ?? new PaymentEntryRepository();
        $this->validator = $validator ?? new PaymentEntryValidator();
        $this->accounting = $accounting ?? new AccountingService();
    }

    /**
     * Create draft payment entry.
     *
     * @agent-use: POST /api/payment-entries
     */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $data['status'] = 'draft';
        $allocations = $input['allocations'] ?? [];
        $normalizedAllocations = array_map(function ($row) {
            $amount = isset($row['allocated_amount']) ? (float) $row['allocated_amount'] : 0;
            if ($amount <= 0) {
                throw new InvalidArgumentException('allocated_amount must be > 0');
            }
            return [
                'reference_type' => $row['reference_type'] ?? null,
                'reference_id' => isset($row['reference_id']) ? (int) $row['reference_id'] : null,
                'allocated_amount' => $amount,
            ];
        }, $allocations);

        $entry = $this->repo->create($data, $normalizedAllocations);
        return ['success' => true, 'data' => $entry];
    }

    /**
     * Submit and post GL.
     *
     * @agent-use: POST /api/payment-entries/{id}/submit
     */
    public function submit(int $id): array
    {
        $entry = $this->repo->findById($id);
        if (! $entry) {
            throw new RuntimeException('Payment entry not found');
        }
        if ($entry['status'] !== 'draft') {
            throw new InvalidArgumentException('Only draft payment entries can be submitted');
        }
        $this->postGL($entry, false);
        $this->repo->updateStatus($id, 'submitted');
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    /**
     * Cancel and reverse GL.
     *
     * @agent-use: POST /api/payment-entries/{id}/cancel
     */
    public function cancel(int $id): array
    {
        $entry = $this->repo->findById($id);
        if (! $entry) {
            throw new RuntimeException('Payment entry not found');
        }
        if ($entry['status'] !== 'submitted') {
            throw new InvalidArgumentException('Only submitted payment entries can be cancelled');
        }
        $this->postGL($entry, true);
        $this->repo->updateStatus($id, 'cancelled');
        return ['success' => true, 'data' => $this->repo->findById($id)];
    }

    public function get(int $id): array
    {
        $entry = $this->repo->findById($id);
        if (! $entry) {
            throw new RuntimeException('Payment entry not found');
        }
        return ['success' => true, 'data' => $entry];
    }

    private function postGL(array $entry, bool $reverse): void
    {
        $amount = $entry['amount'];
        $entries = $reverse
            ? [
                [
                    'account_id' => $entry['credit_account_id'],
                    'debit' => $amount,
                    'credit' => 0,
                    'reference_type' => 'payment_entry',
                    'reference_id' => $entry['id'],
                    'remarks' => 'Reverse payment entry ' . $entry['id'],
                ],
                [
                    'account_id' => $entry['debit_account_id'],
                    'debit' => 0,
                    'credit' => $amount,
                    'reference_type' => 'payment_entry',
                    'reference_id' => $entry['id'],
                    'remarks' => 'Reverse payment entry ' . $entry['id'],
                ],
            ]
            : [
                [
                    'account_id' => $entry['debit_account_id'],
                    'debit' => $amount,
                    'credit' => 0,
                    'reference_type' => 'payment_entry',
                    'reference_id' => $entry['id'],
                    'remarks' => 'Payment entry ' . $entry['id'],
                ],
                [
                    'account_id' => $entry['credit_account_id'],
                    'debit' => 0,
                    'credit' => $amount,
                    'reference_type' => 'payment_entry',
                    'reference_id' => $entry['id'],
                    'remarks' => 'Payment entry ' . $entry['id'],
                ],
            ];

        $this->accounting->postJournal([
            'posting_date' => $entry['reference_date'] ?? date('Y-m-d'),
            'entries' => $entries,
        ]);
    }
}
