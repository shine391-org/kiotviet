<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Services\Loyalty\LoyaltyService;
use CodeIgniter\API\ResponseTrait;

class LoyaltyController extends BaseController
{
    use ResponseTrait;

    protected LoyaltyService $service;

    public function __construct()
    {
        $this->service = service('loyaltyService');
    }

    /** Get customer wallet. GET /api/loyalty/wallet/{customerId} */
    public function wallet($customerId)
    {
        return $this->wrap(fn () => $this->respond($this->service->getWallet((int) $customerId)));
    }

    /** Calculate earn points. POST /api/loyalty/calculate-earn */
    public function calculateEarn()
    {
        $input = $this->safeInput();

        $validation = $this->validateLoyaltyInput($input, ['customer_id', 'order_total']);
        if ($validation !== null) {
            return $validation;
        }

        return $this->wrap(fn () => $this->respond(
            $this->service->calculateEarnPoints((int) $input['customer_id'], (float) $input['order_total'])
        ));
    }

    /** Preview redeem. POST /api/loyalty/redeem-preview */
    public function redeemPreview()
    {
        $input = $this->safeInput();

        $validation = $this->validateLoyaltyInput($input, ['customer_id', 'points']);
        if ($validation !== null) {
            return $validation;
        }

        return $this->wrap(fn () => $this->respond(
            $this->service->redeemPreview((int) $input['customer_id'], (float) $input['points'])
        ));
    }

    /** Earn points after order. POST /api/loyalty/earn */
    public function earn()
    {
        $input = $this->safeInput();

        $validation = $this->validateLoyaltyInput($input, ['customer_id', 'order_total']);
        if ($validation !== null) {
            return $validation;
        }

        return $this->wrap(fn () => $this->respond(
            $this->service->earnPoints((int) $input['customer_id'], (float) $input['order_total'], $input['order_id'] ?? null)
        ));
    }

    /** Redeem points. POST /api/loyalty/redeem */
    public function redeem()
    {
        $input = $this->safeInput();

        $validation = $this->validateLoyaltyInput($input, ['customer_id', 'points']);
        if ($validation !== null) {
            return $validation;
        }

        return $this->wrap(fn () => $this->respond(
            $this->service->redeemPoints((int) $input['customer_id'], (float) $input['points'], $input['order_id'] ?? null)
        ));
    }

    /** Get transaction history. GET /api/loyalty/transactions/{customerId} */
    public function transactions($customerId)
    {
        return $this->wrap(fn () => $this->respond($this->service->getTransactions((int) $customerId)));
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
            log_message('error', 'LoyaltyController error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->failServerError('Internal server error');
        }
    }

    private function safeInput(): array
    {
        try {
            $json = $this->request->getJSON(true);
            if (is_array($json)) return $json;
        } catch (\Throwable $e) {}
        $raw = $this->request->getRawInput();
        return is_array($raw) ? $raw : [];
    }

    /**
     * Validate loyalty input fields.
     * @return \CodeIgniter\HTTP\ResponseInterface|null Returns error response or null if valid
     */
    private function validateLoyaltyInput(array $input, array $requiredFields): ?\CodeIgniter\HTTP\ResponseInterface
    {
        $errors = [];

        if (in_array('customer_id', $requiredFields, true)) {
            if (!isset($input['customer_id'])) {
                $errors['customer_id'] = 'customer_id is required';
            } elseif (!is_numeric($input['customer_id']) || (int) $input['customer_id'] <= 0) {
                $errors['customer_id'] = 'customer_id must be a positive integer';
            }
        }

        if (in_array('order_total', $requiredFields, true)) {
            if (!isset($input['order_total'])) {
                $errors['order_total'] = 'order_total is required';
            } elseif (!is_numeric($input['order_total'])) {
                $errors['order_total'] = 'order_total must be numeric';
            } elseif ((float) $input['order_total'] < 0) {
                $errors['order_total'] = 'order_total must be non-negative';
            }
        }

        if (in_array('points', $requiredFields, true)) {
            if (!isset($input['points'])) {
                $errors['points'] = 'points is required';
            } elseif (!is_numeric($input['points']) || (float) $input['points'] <= 0) {
                $errors['points'] = 'points must be a positive number';
            }
        }

        if (!empty($errors)) {
            return $this->failValidationErrors($errors);
        }

        return null;
    }
}
