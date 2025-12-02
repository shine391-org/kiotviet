<?php

namespace App\Services\Payments;

use App\Repositories\Payments\PaymentEntryRepository;
use App\Validators\PaymentEntryValidator;

/**
 * @agent-service: Payment entry
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class PaymentEntryService
{
    protected PaymentEntryRepository $repo;
    protected PaymentEntryValidator $validator;

    public function __construct(?PaymentEntryRepository $repo = null, ?PaymentEntryValidator $validator = null)
    {
        $this->repo = $repo ?? new PaymentEntryRepository();
        $this->validator = $validator ?? new PaymentEntryValidator();
    }

    /**
     * Idempotent create by order + method + reference.
     *
     * @agent-use: POS checkout
     */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $existing = $this->repo->findByKey($data['order_id'], $data['payment_method'], $data['reference']);
        if ($existing) {
            return ['success' => true, 'data' => $existing];
        }
        $created = $this->repo->create($data);
        return ['success' => true, 'data' => $created];
    }

    public function settle(int $id): void
    {
        $this->repo->updateStatus($id, 'settled');
    }

    public function refund(int $id): void
    {
        $this->repo->updateStatus($id, 'refunded');
    }
}
