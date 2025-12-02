<?php

namespace App\Services\Accounting;

use App\Repositories\Accounting\ExchangeRateRepository;
use App\Validators\ExchangeRateValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-service: Currency conversion
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class CurrencyService
{
    protected ExchangeRateRepository $rates;
    protected ExchangeRateValidator $validator;
    protected string $baseCurrency = 'VND';

    public function __construct(?ExchangeRateRepository $rates = null, ?ExchangeRateValidator $validator = null)
    {
        $this->rates = $rates ?? new ExchangeRateRepository();
        $this->validator = $validator ?? new ExchangeRateValidator();
    }

    public function createRate(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $rate = $this->rates->create($data);
        return ['success' => true, 'data' => $rate];
    }

    public function convert(float $amount, string $from, string $to, string $date): float
    {
        $from = strtoupper(trim($from));
        $to = strtoupper(trim($to));
        if ($from === $to) {
            return $amount;
        }
        $base = $this->baseCurrency;
        $asOf = $this->normalizeDate($date);
        $baseAmount = $from === $base ? $amount : $amount * $this->getRate($from, $asOf);
        return $to === $base ? round($baseAmount, 2) : round($baseAmount / $this->getRate($to, $asOf), 2);
    }

    public function getRate(string $currency, \DateTimeInterface $asOf): float
    {
        $currency = strtoupper(trim($currency));
        if ($currency === $this->baseCurrency) {
            return 1.0;
        }
        $rate = $this->rates->latestRate($currency, $asOf->format('Y-m-d'));
        if (! $rate) {
            throw new RuntimeException('Exchange rate not found for ' . $currency);
        }
        return (float) $rate['rate'];
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
