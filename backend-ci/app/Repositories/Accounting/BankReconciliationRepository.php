<?php

namespace App\Repositories\Accounting;

use App\Models\BankReconciliationModel;
use App\Models\BankReconciliationLogModel;
use CodeIgniter\Database\BaseConnection;

/**
 * @agent-repository: Bank reconciliation
 * @agent-pattern: Repository with logs
 * @agent-reusable: MEDIUM
 */
class BankReconciliationRepository
{
    protected BankReconciliationModel $reco;
    protected BankReconciliationLogModel $logs;
    protected BaseConnection $db;

    public function __construct(
        ?BankReconciliationModel $reco = null,
        ?BankReconciliationLogModel $logs = null,
        ?BaseConnection $db = null
    ) {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->reco = $reco ?? new BankReconciliationModel();
        $this->logs = $logs ?? new BankReconciliationLogModel();
    }

    public function createPending(int $statementId): array
    {
        $payload = [
            'bank_statement_id' => $statementId,
            'status' => 'pending',
            'matched_amount' => 0,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
        $this->reco->insert($payload);
        $payload['id'] = (int) $this->reco->getInsertID();
        return $payload;
    }

    public function linkPayment(int $recoId, int $paymentEntryId, float $amount): bool
    {
        return (bool) $this->reco->update($recoId, [
            'payment_entry_id' => $paymentEntryId,
            'matched_amount' => $amount,
            'status' => 'reconciled',
            'updated_at' => $this->now(),
        ]);
    }

    public function findById(int $id): ?array
    {
        $row = $this->reco->find($id);
        return $row ? $this->hydrate($row) : null;
    }

    public function log(int $recoId, string $action, string $message): void
    {
        $this->logs->insert([
            'bank_reconciliation_id' => $recoId,
            'action' => $action,
            'message' => $message,
            'created_at' => $this->now(),
        ]);
    }

    private function hydrate(array $row): array
    {
        $row['id'] = isset($row['id']) ? (int) $row['id'] : null;
        $row['bank_statement_id'] = isset($row['bank_statement_id']) ? (int) $row['bank_statement_id'] : null;
        $row['payment_entry_id'] = isset($row['payment_entry_id']) ? (int) $row['payment_entry_id'] : null;
        $row['matched_amount'] = isset($row['matched_amount']) ? (float) $row['matched_amount'] : 0.0;
        return $row;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
