<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\BankAccounts\BankAccountService;
use CodeIgniter\API\ResponseTrait;

/**
 * Bank Accounts API
 *
 * @agent-controller: BankAccounts
 * @agent-pattern: Thin controller - routing only
 */
class BankAccountsController extends BaseController
{
    use ResponseTrait;

    protected BankAccountService $service;

    public function __construct()
    {
        $this->service = service('bankAccountService');
    }

    /** List bank accounts. @agent-use: GET /api/bank-accounts */
    public function index()
    {
        return $this->wrap(fn () => $this->respond($this->service->list($this->request->getGet())));
    }

    /** Get bank account detail. @agent-use: GET /api/bank-accounts/{id} */
    public function show($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->get((int) $id)));
    }

    /** Create bank account. @agent-use: POST /api/bank-accounts */
    public function create()
    {
        return $this->wrap(fn () => $this->respondCreated($this->service->create($this->safeInput())));
    }

    /** Update bank account. @agent-use: PUT /api/bank-accounts/{id} */
    public function update($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->update((int) $id, $this->safeInput())));
    }

    /** Delete bank account. @agent-use: DELETE /api/bank-accounts/{id} */
    public function delete($id)
    {
        return $this->wrap(fn () => $this->respond($this->service->delete((int) $id)));
    }

    /** Generate QR code for payment. @agent-use: GET /api/bank-accounts/{id}/qr */
    public function qr($id)
    {
        $amount = (float) ($this->request->getGet('amount') ?? 0);
        $description = $this->request->getGet('description');
        return $this->wrap(fn () => $this->respond($this->service->generateQR((int) $id, $amount, $description)));
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
            return $this->failServerError($e->getMessage());
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
