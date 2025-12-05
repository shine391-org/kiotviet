<?php

namespace App\Services\Accounting;

use App\Repositories\Accounting\GLEntryRepository;
use InvalidArgumentException;

/**
 * @agent-service: Aging reports
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class AgingService
{
    protected GLEntryRepository $gl;
    protected CurrencyService $currency;

    public function __construct(?GLEntryRepository $gl = null, ?CurrencyService $currency = null)
    {
        $this->gl = $gl ?? new GLEntryRepository();
        $this->currency = $currency ?? new CurrencyService();
    }

    /**
     * Build AR/AP aging buckets grouped by party.
     *
     * @agent-use: GET /api/aging
     */
    public function report(array $filters): array
    {
        $partyType = $filters['party_type'] ?? 'customer';
        $asOf = $this->normalizeDate($filters['as_of'] ?? date('Y-m-d'));
        $baseCurrency = $filters['base_currency'] ?? null;
        $rows = $this->gl->listByFilters([
            'party_type' => $partyType,
            'party_id' => $filters['party_id'] ?? null,
        ]);

        $buckets = [];
        foreach ($rows as $row) {
            $balance = ($row['debit'] ?? 0) - ($row['credit'] ?? 0);
            if (abs($balance) < 0.01) { continue; }
            $currency = $row['currency'] ?? 'VND';
            $amount = $balance;
            if ($baseCurrency && strtoupper($baseCurrency) !== strtoupper($currency)) {
                $amount = $this->currency->convert($balance, $currency, $baseCurrency, $asOf->format('Y-m-d'));
                $currency = $baseCurrency;
            }
            $days = $this->daysDiff($asOf, $row['posting_date'] ?? date('Y-m-d'));
            $bucketKey = $this->bucketKey($days);
            $partyId = $row['party_id'] ?? 0;
            $buckets[$partyId][$currency][$bucketKey] = ($buckets[$partyId][$currency][$bucketKey] ?? 0) + $amount;
        }

        $data = [];
        foreach ($buckets as $partyId => $currencies) {
            foreach ($currencies as $currency => $bucketVals) {
                $data[] = [
                    'party_id' => (int) $partyId,
                    'currency' => $currency,
                    'bucket_0_30' => round($bucketVals['0-30'] ?? 0, 2),
                    'bucket_31_60' => round($bucketVals['31-60'] ?? 0, 2),
                    'bucket_61_90' => round($bucketVals['61-90'] ?? 0, 2),
                    'bucket_90_plus' => round($bucketVals['90+'] ?? 0, 2),
                ];
            }
        }

        return ['success' => true, 'data' => array_values($data)];
    }

    private function bucketKey(int $days): string
    {
        if ($days <= 30) { return '0-30'; }
        if ($days <= 60) { return '31-60'; }
        if ($days <= 90) { return '61-90'; }
        return '90+';
    }

    private function daysDiff(\DateTimeInterface $asOf, string $postingDate): int
    {
        $post = $this->normalizeDate($postingDate);
        return (int) $post->diff($asOf)->format('%a');
    }

    private function normalizeDate($date): \DateTimeInterface
    {
        try {
            return new \DateTime($date);
        } catch (\Throwable $e) {
            throw new InvalidArgumentException('invalid date');
        }
    }
}
