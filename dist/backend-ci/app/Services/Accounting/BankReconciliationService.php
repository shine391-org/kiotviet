<?php

namespace App\Services\Accounting;

use App\Repositories\Accounting\BankReconciliationRepository;
use App\Repositories\Accounting\BankStatementRepository;
use App\Repositories\Accounting\PaymentEntryRepository;
use App\Validators\BankReconciliationValidator;
use RuntimeException;

/**
 * @agent-service: Bank reconciliation
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class BankReconciliationService
{
    protected BankStatementRepository $statements;
    protected BankReconciliationRepository $reconciliations;
    protected PaymentEntryRepository $payments;
    protected BankReconciliationValidator $validator;

    public function __construct(
        ?BankStatementRepository $statements = null,
        ?BankReconciliationRepository $reconciliations = null,
        ?PaymentEntryRepository $payments = null,
        ?BankReconciliationValidator $validator = null
    ) {
        $this->statements = $statements ?? new BankStatementRepository();
        $this->reconciliations = $reconciliations ?? new BankReconciliationRepository();
        $this->payments = $payments ?? new PaymentEntryRepository();
        $this->validator = $validator ?? new BankReconciliationValidator();
    }

    /**
     * Import statements and auto-match by reference_no + amount.
     *
     * @agent-use: POST /api/bank-statements/import
     */
    public function import(array $input): array
    {
        $lines = $this->validator->validateImport($input);
        $inserted = $this->statements->import($lines);
        $reconciled = [];
        foreach ($inserted as $line) {
            $reco = $this->reconciliations->createPending((int) $line['id']);
            $match = null;
            if (! empty($line['reference_no'])) {
                $match = $this->payments->findByReference($line['reference_no'], (float) $line['amount']);
            }
            if ($match) {
                $this->reconciliations->linkPayment((int) $reco['id'], (int) $match['id'], (float) $line['amount']);
                $this->reconciliations->log((int) $reco['id'], 'auto_match', 'Matched by reference');
                $reconciled[] = $reco['id'];
            }
        }
        return ['success' => true, 'auto_matched' => $reconciled];
    }

    /**
     * Manual reconcile a statement with a payment entry.
     *
     * @agent-use: POST /api/bank-reconciliations/{id}/match
     */
    public function reconcile(int $recoId, int $paymentEntryId, float $amount): array
    {
        $reco = $this->reconciliations->findById($recoId);
        if (! $reco) {
            throw new RuntimeException('Reconciliation not found');
        }
        $payment = $this->payments->findById($paymentEntryId);
        if (! $payment) {
            throw new RuntimeException('Payment entry not found');
        }
        $this->reconciliations->linkPayment($recoId, $paymentEntryId, $amount);
        $this->reconciliations->log($recoId, 'manual_match', 'Manually matched');
        return ['success' => true, 'data' => $this->reconciliations->findById($recoId)];
    }
}
