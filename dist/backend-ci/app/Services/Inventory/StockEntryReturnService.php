<?php

namespace App\Services\Inventory;

use InvalidArgumentException;

/**
 * Stock return entry helper.
 *
 * @agent-service: Stock return
 * @agent-pattern: Wrapper on stock entry
 * @agent-reusable: MEDIUM
 */
class StockEntryReturnService
{
    protected StockEntryService $entries;

    public function __construct(?StockEntryService $entries = null)
    {
        $this->entries = $entries ?? new StockEntryService();
    }

    /**
     * Create + submit return entry.
     *
     * @agent-use: POST /api/stock-entries/returns
     * @agent-pattern: Thin wrapper
     */
    public function processReturn(array $input): array
    {
        if (empty($input['return_reason'])) {
            throw new InvalidArgumentException('return_reason is required');
        }
        $payload = $input + ['type' => 'return'];
        $entry = $this->entries->create($payload)['data'];
        $submitted = $this->entries->submit((int) $entry['id'])['data'];
        return ['success' => true, 'data' => $submitted];
    }
}
