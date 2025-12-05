<?php

namespace App\Services\Reports;

use App\Validators\ReportValidator;
use CodeIgniter\Database\BaseConnection;
use RuntimeException;

/**
 * Financial & stock reports.
 *
 * @agent-service: Reports
 * @agent-pattern: Read-only aggregation
 * @agent-reusable: MEDIUM
 */
class ReportService
{
    protected BaseConnection $db;
    protected ReportValidator $validator;

    public function __construct(?BaseConnection $db = null, ?ReportValidator $validator = null)
    {
        $this->db = $db ?? \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $this->validator = $validator ?? new ReportValidator();
    }

    /** @agent-use: GET /api/reports/gl */
    public function gl(array $filters): array
    {
        $data = $this->validator->validateDateRange($filters);
        $b = $this->db->table('gl_entries');
        if (! empty($data['from_date'])) { $b->where('posting_date >=', $data['from_date']); }
        if (! empty($data['to_date'])) { $b->where('posting_date <=', $data['to_date']); }
        if (! empty($data['account_id'])) { $b->where('account_id', $data['account_id']); }
        $rows = $b->orderBy('posting_date', 'ASC')->limit(500)->get()->getResultArray();
        $totalDebit = array_sum(array_map(fn ($r) => (float) $r['debit'], $rows));
        $totalCredit = array_sum(array_map(fn ($r) => (float) $r['credit'], $rows));
        return ['success' => true, 'data' => $rows, 'totals' => ['debit' => $totalDebit, 'credit' => $totalCredit]];
    }

    /** @agent-use: GET /api/reports/profit-loss */
    public function profitLoss(array $filters): array
    {
        $data = $this->validator->validateDateRange($filters);
        $rows = $this->gl($data)['data'];
        $accountTypes = $this->accountTypes();
        $revenue = 0; $expense = 0;
        foreach ($rows as $row) {
            $type = $accountTypes[$row['account_id']] ?? null;
            if ($type === 'income') {
                $revenue += (float) $row['credit'] - (float) $row['debit'];
            } elseif ($type === 'expense') {
                $expense += (float) $row['debit'] - (float) $row['credit'];
            }
        }
        return ['success' => true, 'data' => ['revenue' => $revenue, 'expense' => $expense, 'net_profit' => $revenue - $expense]];
    }

    /** @agent-use: GET /api/reports/balance-sheet */
    public function balanceSheet(array $filters): array
    {
        $data = $this->validator->validateDateRange($filters);
        $rows = $this->gl($data)['data'];
        $types = $this->accountTypes();
        $totals = ['asset' => 0, 'liability' => 0, 'equity' => 0];
        foreach ($rows as $row) {
            $type = $types[$row['account_id']] ?? null;
            if ($type === 'asset') {
                $totals['asset'] += (float) $row['debit'] - (float) $row['credit'];
            } elseif ($type === 'liability') {
                $totals['liability'] += (float) $row['credit'] - (float) $row['debit'];
            } elseif ($type === 'equity') {
                $totals['equity'] += (float) $row['credit'] - (float) $row['debit'];
            }
        }
        return ['success' => true, 'data' => $totals];
    }

    /** @agent-use: GET /api/reports/aging */
    public function aging(array $filters): array
    {
        $data = $this->validator->validateAging($filters);
        $rows = $this->db->table('gl_entries')
            ->where('party_type', $data['party_type'])
            ->get()->getResultArray();
        $asOf = strtotime($data['as_of_date']);
        $buckets = ['current' => 0, '30' => 0, '60' => 0, '90' => 0, '120+' => 0];
        foreach ($rows as $row) {
            $days = (int) floor(($asOf - strtotime($row['posting_date'])) / 86400);
            $balance = (float) $row['debit'] - (float) $row['credit'];
            if ($days <= 30) { $buckets['current'] += $balance; }
            elseif ($days <= 60) { $buckets['30'] += $balance; }
            elseif ($days <= 90) { $buckets['60'] += $balance; }
            elseif ($days <= 120) { $buckets['90'] += $balance; }
            else { $buckets['120+'] += $balance; }
        }
        return ['success' => true, 'data' => $buckets];
    }

    /** @agent-use: GET /api/reports/stock-balance */
    public function stockBalance(array $filters): array
    {
        $data = $this->validator->validateStock($filters);
        $b = $this->db->table('stock_bins');
        if (! empty($data['warehouse_id'])) { $b->where('branch_id', $data['warehouse_id']); }
        if (! empty($data['product_id'])) { $b->where('product_id', $data['product_id']); }
        $rows = $b->get()->getResultArray();
        $total = array_sum(array_map(fn ($r) => (float) $r['on_hand_qty'], $rows));
        return ['success' => true, 'data' => $rows, 'total_on_hand' => $total];
    }

    private function accountTypes(): array
    {
        $rows = $this->db->table('chart_of_accounts')->get()->getResultArray();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['id']] = $row['account_type'];
        }
        return $map;
    }
}
