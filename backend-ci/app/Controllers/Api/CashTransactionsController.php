<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\CashTransactions\CashTransactionService;
use CodeIgniter\API\ResponseTrait;

/**
 * Cash transactions API.
 * 
 * @agent-controller: Cash transactions
 * @agent-pattern: Thin controller - delegate to service
 */
class CashTransactionsController extends BaseController
{
    use ResponseTrait;

    protected CashTransactionService $service;

    public function __construct()
    {
        $this->service = new CashTransactionService();
    }

    /**
     * Create receipt transaction.
     * 
     * @agent-use: POST /api/cash/receipt
     * @agent-pattern: Standard create endpoint
     */
    public function createReceipt()
    {
        return $this->wrap(function () {
            $data = $this->safeInput();
            $currentUserId = $this->getCurrentUserId();
            
            // Inject created_by into data
            $data['created_by'] = $currentUserId;
            
            $result = $this->service->createReceipt($data);
            return $this->respondCreated($result);
        });
    }

    /**
     * Create payment transaction.
     * 
     * @agent-use: POST /api/cash/payment
     * @agent-pattern: Standard create endpoint
     */
    public function createPayment()
    {
        return $this->wrap(function () {
            $data = $this->safeInput();
            $currentUserId = $this->getCurrentUserId();
            
            // Inject created_by into data
            $data['created_by'] = $currentUserId;
            
            $result = $this->service->createPayment($data);
            return $this->respondCreated($result);
        });
    }

    /**
     * List transactions with filters.
     * 
     * @agent-use: GET /api/cash/transactions
     * @agent-pattern: Standard list endpoint
     */
    public function list()
    {
        return $this->wrap(function () {
            $filters = $this->request->getGet();
            
            $result = $this->service->listTransactions($filters);
            return $this->respond($result);
        });
    }

    /**
     * Get transaction by ID.
     * 
     * @agent-use: GET /api/cash/transactions/{id}
     * @agent-pattern: Standard get endpoint
     */
    public function show($id)
    {
        return $this->wrap(function () use ($id) {
            $result = $this->service->getTransaction((int) $id);
            return $this->respond($result);
        });
    }

    /**
     * Get balance (all branches).
     * 
     * @agent-use: GET /api/cash/balance
     * @agent-pattern: Balance endpoint
     */
    public function getBalance()
    {
        return $this->wrap(function () {
            $result = $this->service->getBalance();
            return $this->respond($result);
        });
    }

    /**
     * Get balance for specific branch.
     * 
     * @agent-use: GET /api/cash/balance/branch/{id}
     * @agent-pattern: Branch balance endpoint
     */
    public function getBranchBalance($branchId)
    {
        return $this->wrap(function () use ($branchId) {
            $result = $this->service->getBalance((int) $branchId);
            return $this->respond($result);
        });
    }

    /**
     * Get daily report.
     * 
     * @agent-use: GET /api/cash/report/daily
     * @agent-pattern: Report endpoint
     */
    public function dailyReport()
    {
        return $this->wrap(function () {
            $date = $this->request->getGet('date');
            $branchId = $this->request->getGet('branch_id');
            
            $result = $this->service->getDailyReport($date, $branchId ? (int) $branchId : null);
            return $this->respond($result);
        });
    }

    /**
     * Delete transaction (soft delete).
     * 
     * @agent-use: DELETE /api/cash/transactions/{id}
     * @agent-pattern: Standard delete endpoint
     */
    public function delete($id)
    {
        return $this->wrap(function () use ($id) {
            $result = $this->service->deleteTransaction((int) $id);
            return $this->respond($result);
        });
    }

    /**
     * Wrap action with error handling.
     */
    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            return $this->failValidationErrors($e->getMessage());
        } catch (\RuntimeException $e) {
            return $this->failNotFound($e->getMessage());
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }

    /**
     * Get safe input from request.
     */
    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) {
                return $json;
            }
        } catch (\Throwable $e) {
            // Fall through to raw input
        }
        
        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }

    /**
     * Get current user ID from JWT token.
     * TODO: Implement proper JWT parsing
     */
    private function getCurrentUserId(): int
    {
        // For now, return 1 - implement proper JWT parsing later
        // TODO: Parse JWT token to get user ID
        return 1;
    }
}