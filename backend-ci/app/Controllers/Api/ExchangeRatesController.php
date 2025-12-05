<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Accounting\CurrencyService;
use CodeIgniter\API\ResponseTrait;

/**
 * Exchange rates API.
 *
 * @agent-controller: ExchangeRates
 * @agent-pattern: Thin controller - routing only
 */
class ExchangeRatesController extends BaseController
{
    use ResponseTrait;

    protected CurrencyService $service;

    public function __construct()
    {
        $this->service = service('currencyService');
    }

    /** @agent-use: POST /api/exchange-rates */
    public function create()
    {
        $payload = $this->request->getJSON(true) ?? [];
        return $this->wrap(fn () => $this->respondCreated($this->service->createRate($payload)));
    }

    private function wrap(callable $action)
    {
        try { return $action(); }
        catch (\InvalidArgumentException $e) { return $this->failValidationErrors($e->getMessage()); }
        catch (\RuntimeException $e) { return $this->failNotFound($e->getMessage()); }
        catch (\Throwable $e) { return $this->failServerError($e->getMessage()); }
    }
}
