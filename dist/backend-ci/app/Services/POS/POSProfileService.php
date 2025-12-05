<?php

namespace App\Services\POS;

use App\Repositories\POS\POSProfileRepository;
use App\Repositories\PaymentMethods\PaymentMethodRepository;
use App\Validators\POSProfileValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-service: POS profile business logic
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class POSProfileService
{
    protected POSProfileRepository $profiles;
    protected POSProfileValidator $validator;
    protected PaymentMethodRepository $paymentMethods;

    public function __construct(
        ?POSProfileRepository $profiles = null,
        ?POSProfileValidator $validator = null,
        ?PaymentMethodRepository $paymentMethods = null
    ) {
        $this->profiles = $profiles ?? new POSProfileRepository();
        $this->validator = $validator ?? new POSProfileValidator();
        $this->paymentMethods = $paymentMethods ?? new PaymentMethodRepository();
    }

    /**
     * Create POS profile with allowed payment methods.
     *
     * @agent-use: POST /api/pos/profiles
     * @agent-pattern: Validation + repository
     */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $this->assertPaymentMethods($data['payment_methods']);
        $profile = $this->profiles->create($data, $data['payment_methods']);
        return ['success' => true, 'data' => $profile];
    }

    /**
     * Update POS profile.
     *
     * @agent-use: PUT /api/pos/profiles/{id}
     * @agent-pattern: Validation + repository
     */
    public function update(int $id, array $input): array
    {
        $existing = $this->profiles->findById($id);
        if (! $existing) {
            throw new RuntimeException('POS profile not found');
        }
        $data = $this->validator->validateUpdate($input);
        if (is_array($data['payment_methods'])) {
            $this->assertPaymentMethods($data['payment_methods']);
        }
        $updated = $this->profiles->update($id, $data, $data['payment_methods']);
        return ['success' => true, 'data' => $updated];
    }

    /**
     * Fetch profile detail.
     */
    public function show(int $id): array
    {
        $profile = $this->profiles->findById($id);
        if (! $profile) {
            throw new RuntimeException('POS profile not found');
        }
        return ['success' => true, 'data' => $profile];
    }

    /**
     * Resolve profile for user + branch or explicit profile id.
     *
     * @agent-use: GET /api/pos/profiles/resolve
     * @agent-pattern: Resolution helper
     */
    public function resolve(array $input): array
    {
        $payload = $this->validator->validateResolve($input);
        $profile = null;
        if ($payload['profile_id']) {
            $profile = $this->profiles->findById($payload['profile_id']);
        }
        if (! $profile && $payload['user_id']) {
            $profile = $this->profiles->findForUser($payload['user_id'], $payload['branch_id']);
        }
        if (! $profile) {
            throw new RuntimeException('No POS profile configured for user');
        }
        return ['success' => true, 'data' => $profile];
    }

    private function assertPaymentMethods(array $methods): void
    {
        foreach ($methods as $method) {
            $code = strtoupper(is_array($method) ? ($method['payment_method'] ?? '') : (string) $method);
            if ($code === '') {
                throw new InvalidArgumentException('payment_method code is required');
            }
            if (! $this->paymentMethods->findByCode($code)) {
                throw new InvalidArgumentException("Payment method {$code} not found");
            }
        }
    }
}
