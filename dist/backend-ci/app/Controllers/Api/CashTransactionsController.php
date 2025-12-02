<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\CashTransactions\CashTransactionService;
use App\Validators\CashTransactionReferenceValidator;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\API\ResponseTrait;
use RuntimeException;

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
        // Use 'tests' connection when in testing environment to match DevDatabaseTrait
        $connectionGroup = (ENVIRONMENT === 'testing') ? 'tests' : null;
        $db = \Config\Database::connect($connectionGroup);
        
        $this->service = new CashTransactionService(
            null, // repository
            null, // validator
            new CashTransactionReferenceValidator($db) // reference validator
        );
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
            $filters = $this->request->getGet();
            $branchId = $filters['branch_id'] ?? null;

            // Remove branch_id from filters to avoid double-applying
            $balanceFilters = $filters;
            unset($balanceFilters['branch_id']);

            $result = $this->service->getBalance(
                $branchId !== null ? (int) $branchId : null,
                $balanceFilters
            );
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
            $filters = $this->request->getGet();
            $result = $this->service->getBalance((int) $branchId, $filters);
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
            $code = $e->getCode();
            if ($code === 401) {
                return $this->failUnauthorized($e->getMessage());
            }
            if ($code === 403) {
                return $this->failForbidden($e->getMessage());
            }
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
     */
    private function getCurrentUserId(): int
    {
        $request = service('request');
        $authHeader = $request->getHeaderLine('Authorization');

        if (empty($authHeader) || !preg_match('/Bearer\\s+(.*)$/i', $authHeader, $matches)) {
            throw new RuntimeException('Authorization token required', 401);
        }

        $token = trim($matches[1]);

        try {
            $jwt = new \App\Libraries\JwtService();
            $payload = $jwt->decode($token);
            $userId = $payload->data->id ?? $payload->sub ?? null;
            if (!$userId) {
                throw new RuntimeException('Invalid token payload', 401);
            }
            return (int) $userId;
        } catch (\Throwable $e) {
            throw new RuntimeException('Invalid or expired token', 401);
        }
    }
}
