<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\POS\POSSalesService;
use CodeIgniter\API\ResponseTrait;

/**
 * POS Sales API - Quick return, daily report, sellers
 *
 * @agent-controller: POSSales
 * @agent-pattern: Thin controller - routing only
 */
class POSSalesController extends BaseController
{
    use ResponseTrait;

    protected POSSalesService $service;

    public function __construct()
    {
        $this->service = service('posSalesService');
    }

    /** Quick return from invoice. @agent-use: POST /api/pos/quick-return */
    public function quickReturn()
    {
        return $this->wrap(fn () => $this->respondCreated($this->service->quickReturn($this->safeInput())));
    }

    /** Get daily report for POS. @agent-use: GET /api/pos/daily-report */
    public function dailyReport()
    {
        return $this->wrap(fn () => $this->respond($this->service->dailyReport($this->request->getGet())));
    }

    /** Get sellers for POS dropdown. @agent-use: GET /api/pos/sellers */
    public function sellers()
    {
        return $this->wrap(fn () => $this->respond($this->service->getSellers($this->request->getGet())));
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', '[POSSalesController] Unexpected error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError('Internal server error');
        }
    }

    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                return $json;
            }
        } catch (\Throwable $e) {
        }
        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }
}
