<?php

namespace Tests\Services;

use App\Services\Accounting\CurrencyService;
use App\Repositories\Accounting\ExchangeRateRepository;
use App\Validators\ExchangeRateValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;
use InvalidArgumentException;

class CurrencyServiceTest extends CIUnitTestCase
{
    private CurrencyService $service;
    private ExchangeRateFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new ExchangeRateFakeRepo();
        $validator = new class extends ExchangeRateValidator {
            public function validateCreate(array $data): array { return $data; }
        };
        $this->service = new CurrencyService($this->repo, $validator);
    }

    public function testCreateRateSuccess(): void
    {
        $data = ['currency' => 'USD', 'rate' => 24000];
        $result = $this->service->createRate($data);
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testConvertSameCurrencyReturnsOriginal(): void
    {
        $result = $this->service->convert(1000, 'VND', 'VND', '2024-01-01');
        
        $this->assertEquals(1000, $result);
    }

    public function testConvertToBaseCurrency(): void
    {
        $result = $this->service->convert(100, 'USD', 'VND', '2024-01-01');
        
        $this->assertEquals(2400000, $result);
    }

    public function testConvertFromBaseCurrency(): void
    {
        $result = $this->service->convert(2400000, 'VND', 'USD', '2024-01-01');
        
        $this->assertEquals(100, $result);
    }

    public function testConvertTrimsAndUppercases(): void
    {
        $result = $this->service->convert(1000, '  vnd  ', '  vnd  ', '2024-01-01');
        
        $this->assertEquals(1000, $result);
    }

    public function testGetRateForBaseCurrencyReturnsOne(): void
    {
        $result = $this->service->getRate('VND', new \DateTime('2024-01-01'));
        
        $this->assertEquals(1.0, $result);
    }

    public function testGetRateSuccess(): void
    {
        $result = $this->service->getRate('USD', new \DateTime('2024-01-01'));
        
        $this->assertEquals(24000, $result);
    }

    public function testGetRateThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Exchange rate not found for JPY');
        
        $this->service->getRate('JPY', new \DateTime('2024-01-01'));
    }

    public function testConvertWithInvalidDateThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid date');
        
        $this->service->convert(100, 'USD', 'VND', 'invalid-date');
    }

    public function testGetRateTrimsAndUppercases(): void
    {
        $result = $this->service->getRate('  usd  ', new \DateTime('2024-01-01'));
        
        $this->assertEquals(24000, $result);
    }
}

class ExchangeRateFakeRepo extends ExchangeRateRepository
{
    private array $ratesData = [
        'USD' => ['id' => 1, 'currency' => 'USD', 'rate' => 24000],
        'EUR' => ['id' => 2, 'currency' => 'EUR', 'rate' => 26000],
    ];

    public function __construct() {}

    public function latestRate(string $currency, string $date): ?array
    {
        return $this->ratesData[$currency] ?? null;
    }

    public function create(array $data): array
    {
        $id = count($this->ratesData) + 1;
        $this->ratesData[$data['currency']] = array_merge($data, ['id' => $id]);
        return $this->ratesData[$data['currency']];
    }
}
