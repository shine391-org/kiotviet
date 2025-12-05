<?php

namespace App\Services\Accounting;

use App\Repositories\Accounting\COARepository;
use App\Repositories\Accounting\GLEntryRepository;
use App\Validators\GLEntryValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-service: Accounting GL posting
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class AccountingService
{
    protected COARepository $coa;
    protected GLEntryRepository $glRepo;
    protected GLEntryValidator $validator;

    public function __construct(
        ?COARepository $coa = null,
        ?GLEntryRepository $glRepo = null,
        ?GLEntryValidator $validator = null
    ) {
        $this->coa = $coa ?? new COARepository();
        $this->glRepo = $glRepo ?? new GLEntryRepository();
        $this->validator = $validator ?? new GLEntryValidator();
    }

    /**
     * Post balanced journal entries.
     *
     * @agent-use: POST /api/gl/journal
     * @agent-pattern: Validate -> balance check -> repo batch insert
     */
    public function postJournal(array $input): array
    {
        $postingDate = $input['posting_date'] ?? null;
        $entriesInput = $input['entries'] ?? [];
        if (! is_array($entriesInput) || $entriesInput === []) {
            throw new InvalidArgumentException('entries are required');
        }

        $validated = [];
        foreach ($entriesInput as $entry) {
            $validatedEntry = $this->validator->validateEntry($entry + ['posting_date' => $postingDate]);
            $this->assertAccountExists($validatedEntry['account_id']);
            $validated[] = $validatedEntry;
        }

        $this->ensureBalanced($validated);

        $entries = $this->glRepo->createBatch($validated);
        return ['success' => true, 'data' => $entries];
    }

    /**
     * Query GL entries.
     *
     * @agent-use: GET /api/gl
     */
    public function list(array $filters): array
    {
        return ['success' => true, 'data' => $this->glRepo->listByFilters($filters)];
    }

    private function ensureBalanced(array $entries): void
    {
        $debit = 0.0;
        $credit = 0.0;
        foreach ($entries as $entry) {
            $debit += $entry['debit'];
            $credit += $entry['credit'];
        }
        if (round($debit, 2) !== round($credit, 2)) {
            throw new InvalidArgumentException('entries not balanced');
        }
    }

    private function assertAccountExists(int $accountId): void
    {
        if (! $this->coa->findById($accountId)) {
            throw new RuntimeException('Account not found: ' . $accountId);
        }
    }
}
