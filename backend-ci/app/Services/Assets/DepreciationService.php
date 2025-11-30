<?php

namespace App\Services\Assets;

use App\Repositories\Assets\DepreciationScheduleRepository;
use App\Repositories\Assets\AssetRepository;
use App\Repositories\Accounting\GLEntryRepository;
use App\Validators\DepreciationValidator;
use RuntimeException;

/**
 * Depreciation scheduler.
 *
 * @agent-service: Depreciation
 * @agent-pattern: Schedule + GL posting
 * @agent-reusable: MEDIUM
 */
class DepreciationService
{
    protected DepreciationScheduleRepository $repo;
    protected AssetRepository $assets;
    protected GLEntryRepository $gl;
    protected DepreciationValidator $validator;

    public function __construct(
        ?DepreciationScheduleRepository $repo = null,
        ?AssetRepository $assets = null,
        ?GLEntryRepository $gl = null,
        ?DepreciationValidator $validator = null
    ) {
        $this->repo = $repo ?? new DepreciationScheduleRepository();
        $this->assets = $assets ?? new AssetRepository();
        $this->gl = $gl ?? new GLEntryRepository();
        $this->validator = $validator ?? new DepreciationValidator();
    }

    /** @agent-use: POST /api/depreciation-schedules */
    public function createSchedule(array $input): array
    {
        $data = $this->validator->validateSchedule($input);
        $asset = $this->requireAsset((int) $data['asset_id']);
        $costBase = (float) ($asset['cost'] ?? 0) - (float) ($asset['salvage_value'] ?? 0);
        $perPeriod = round($costBase * ($data['rate'] / 100) / 12, 2);
        $startDate = $data['start_date'] ?? date('Y-m-d');
        $lines = [];
        for ($i = 1; $i <= $data['total_periods']; $i++) {
            $postingDate = date('Y-m-d', strtotime("+".($i - 1)." month", strtotime($startDate)));
            $lines[] = [
                'period_no' => $i,
                'posting_date' => $postingDate,
                'amount' => $perPeriod,
            ];
        }
        $schedule = [
            'asset_id' => $asset['id'],
            'method' => $data['method'],
            'rate' => $data['rate'],
            'start_date' => $startDate,
            'total_periods' => $data['total_periods'],
        ];
        $created = $this->repo->create($schedule, $lines);
        return ['success' => true, 'data' => $created];
    }

    /** @agent-use: POST /api/depreciation-schedules/{id}/post/{period} */
    public function postLine(array $input): array
    {
        $data = $this->validator->validatePost($input);
        $line = $this->repo->findLine((int) $data['schedule_id'], (int) $data['period_no']);
        if (! $line) {
            throw new RuntimeException('Schedule line not found');
        }
        if (! empty($line['posted_gl_entry_id'])) {
            return ['success' => true, 'data' => $line];
        }

        $expenseAccount = $data['expense_account_id'] ?? 2;
        $assetAccount = $data['account_id'] ?? 1;
        $glId = $this->insertGlEntries($line, $assetAccount, $expenseAccount);
        $this->repo->markPosted((int) $line['id'], $glId);
        $line['posted_gl_entry_id'] = $glId;
        return ['success' => true, 'data' => $line];
    }

    private function insertGlEntries(array $line, int $assetAccount, int $expenseAccount): int
    {
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $now = date('Y-m-d H:i:s');
        $entries = [
            [
                'account_id' => $expenseAccount,
                'posting_date' => $line['posting_date'],
                'debit' => $line['amount'],
                'credit' => 0,
                'reference_type' => 'asset_depreciation',
                'reference_id' => $line['schedule_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'account_id' => $assetAccount,
                'posting_date' => $line['posting_date'],
                'debit' => 0,
                'credit' => $line['amount'],
                'reference_type' => 'asset_depreciation',
                'reference_id' => $line['schedule_id'],
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];
        $db->transStart();
        $db->table('gl_entries')->insertBatch($entries);
        $firstId = (int) $db->insertID();
        $db->transComplete();
        return $firstId;
    }

    private function requireAsset(int $id): array
    {
        $row = $this->assets->find($id);
        if (! $row) {
            throw new RuntimeException('Asset not found');
        }
        return $row;
    }
}
