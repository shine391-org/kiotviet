<?php

namespace App\Services\Orders;

use App\Repositories\Orders\OrderTemplateRepository;
use App\Validators\OrderTemplateValidator;
use InvalidArgumentException;
use RuntimeException;

/**
 * Order template business logic.
 *
 * @agent-service: Order templates
 * @agent-pattern: Service orchestrator
 * @agent-reusable: MEDIUM
 */
class OrderTemplateService
{
    protected OrderTemplateRepository $repo;
    protected OrderTemplateValidator $validator;
    protected OrderService $orders;

    public function __construct(
        ?OrderTemplateRepository $repo = null,
        ?OrderTemplateValidator $validator = null,
        ?OrderService $orders = null
    ) {
        $this->repo = $repo ?? new OrderTemplateRepository();
        $this->validator = $validator ?? new OrderTemplateValidator();
        $this->orders = $orders ?? new OrderService();
    }

    /** List templates. @agent-use: GET /api/order-templates */
    public function list(array $filters): array
    {
        $validated = $this->validator->validateFilters($filters);
        return [
            'success' => true,
            'data' => $this->repo->list($validated),
            'total' => $this->repo->count($validated),
        ];
    }

    /** Show template. */
    public function show(int $id): array
    {
        return ['success' => true, 'data' => $this->requireTemplate($id)];
    }

    /** Create template with items. */
    public function createTemplate(array $data): array
    {
        $validated = $this->validator->validateCreate($data);
        if ($this->repo->existsByName($validated['name'])) {
            throw new InvalidArgumentException('Template name already exists');
        }
        $template = [
            'name' => $validated['name'],
            'customer_id' => $validated['customer_id'] ?? null,
            'frequency' => $validated['frequency'] ?? null,
            'is_active' => $validated['is_active'] ? 1 : 0,
            'notes' => $validated['notes'] ?? null,
        ];
        $created = $this->repo->create($template, $validated['items']);
        return ['success' => true, 'data' => $created];
    }

    /** Update template metadata/items. */
    public function updateTemplate(int $id, array $data): array
    {
        $existing = $this->requireTemplate($id);
        $validated = $this->validator->validateUpdate($data);
        if (isset($validated['name']) && $this->repo->existsByName($validated['name'], $id)) {
            throw new InvalidArgumentException('Template name already exists');
        }
        $template = $validated;
        $items = null;
        if (isset($validated['items'])) {
            $items = $validated['items'];
            unset($template['items']);
        }
        $updated = $this->repo->update($id, $template, $items);
        return ['success' => true, 'data' => $updated + ['items' => $items ?? $existing['items']]];
    }

    /** Delete template. */
    public function deleteTemplate(int $id): array
    {
        $this->requireTemplate($id);
        $this->repo->delete($id);
        return ['success' => true];
    }

    /**
     * Apply template to create a draft order.
     *
     * @agent-use: Quick order creation
     * @agent-pattern: Delegate to OrderService with template items
     */
    public function applyTemplate(int $id, array $payload): array
    {
        $template = $this->requireTemplate($id);
        if (empty($template['is_active'])) {
            throw new InvalidArgumentException('Template is inactive');
        }
        $orderPayload = $this->buildOrderPayload($template, $payload);
        $orderResult = $this->orders->create($orderPayload);
        return ['success' => true, 'data' => $orderResult['data']];
    }

    private function buildOrderPayload(array $template, array $input): array
    {
        $items = [];
        foreach ($template['items'] as $item) {
            $items[] = [
                'product_id' => (int) $item['product_id'],
                'variant_id' => isset($item['variant_id']) ? (int) $item['variant_id'] : null,
                'quantity' => (float) $item['quantity'],
            ];
        }
        $customerId = $input['customer_id'] ?? ($template['customer_id'] ?? null);
        return [
            'customer_id' => $customerId,
            'customer_group_id' => $input['customer_group_id'] ?? null,
            'order_type' => $input['order_type'] ?? 'shipping',
            'payment_method' => $input['payment_method'] ?? null,
            'order_date' => $input['order_date'] ?? date('Y-m-d'),
            'branch_id' => isset($input['branch_id']) ? (int) $input['branch_id'] : 0,
            'shipping_fee' => isset($input['shipping_fee']) ? (float) $input['shipping_fee'] : 0,
            'paid_amount' => isset($input['paid_amount']) ? (float) $input['paid_amount'] : 0,
            'payments' => $input['payments'] ?? null,
            'notes' => $input['notes'] ?? ($template['notes'] ?? null),
            'shipping_name' => $input['shipping_name'] ?? null,
            'shipping_phone' => $input['shipping_phone'] ?? null,
            'shipping_address' => $input['shipping_address'] ?? null,
            'shipping_ward' => $input['shipping_ward'] ?? null,
            'shipping_district' => $input['shipping_district'] ?? null,
            'shipping_city' => $input['shipping_city'] ?? null,
            'items' => $items,
        ];
    }

    private function requireTemplate(int $id): array
    {
        $template = $this->repo->findWithItems($id);
        if (! $template) {
            throw new RuntimeException('Order template not found');
        }
        return $template;
    }
}
