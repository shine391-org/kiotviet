<?php

namespace App\Services\Attributes;

use App\Repositories\Attributes\AttributeRepository;
use App\Validators\AttributeValidator;
use InvalidArgumentException;
use RuntimeException;

/** Attribute business logic. @agent-service: Attribute service layer @agent-pattern: Service orchestrator @agent-reusable: HIGH */
class AttributeService
{
    protected AttributeRepository $repo; protected AttributeValidator $validator;
    public function __construct(?AttributeRepository $repo = null, ?AttributeValidator $validator = null) { $this->repo = $repo ?? new AttributeRepository(); $this->validator = $validator ?? new AttributeValidator(); }

    /** List attributes. @agent-use: GET /api/attributes @agent-pattern: Standard list */
    public function list(array $filters): array
    {
        $validated = $this->validator->validateList($filters);
        return ['success' => true, 'data' => $this->repo->findAll($validated)];
    }

    /** Show attribute. @agent-use: GET /api/attributes/{id} @agent-pattern: Get by id */
    public function show(int $id): array
    {
        return ['success' => true, 'data' => $this->requireAttribute($id)];
    }

    /** Create attribute. @agent-use: POST /api/attributes @agent-pattern: Standard create */
    public function create(array $data): array
    {
        helper('text');
        $validated = $this->validator->validateAttributeCreate($data);

        $defaults = [
            'type' => 'select',
            'is_required' => 0,
            'is_filterable' => 1,
            'is_visible' => 1,
            'sort_order' => 0,
            'status' => 'active',
            'slug' => url_title($validated['name'], '-', true),
            'attribute_key' => uniqid('attr_', true),
        ];

        // Merge validated data with defaults. Validated data takes precedence.
        $payload = array_merge($defaults, $validated);

        $attr = $this->repo->create($payload);
        return ['success' => true, 'data' => $attr];
    }

    /** Update attribute. @agent-use: PUT /api/attributes/{id} @agent-pattern: Standard update */
    public function update(int $id, array $data): array
    {
        $this->requireAttribute($id);
        $validated = $this->validator->validateAttributeUpdate($data);
        $this->repo->update($id, $validated);
        return ['success' => true];
    }

    /** Delete attribute. @agent-use: DELETE /api/attributes/{id} @agent-pattern: Soft delete */
    public function delete(int $id): array
    {
        $this->requireAttribute($id);
        $this->repo->delete($id);
        return ['success' => true];
    }

    /** List options. @agent-use: GET /api/attributes/{id}/options @agent-pattern: Nested listing */
    public function options(int $attributeId): array
    {
        $this->requireAttribute($attributeId);
        return ['success' => true, 'data' => $this->repo->options($attributeId)];
    }

    /** Create option. @agent-use: POST /api/attributes/{id}/options @agent-pattern: Standard create */
    public function createOption(int $attributeId, array $data): array
    {
        $this->requireAttribute($attributeId);
        $payload = $this->validator->validateOption($data, true);
        $option = $this->repo->createOption($attributeId, $payload);
        return ['success' => true, 'data' => $option];
    }

    /** Update option. @agent-use: PUT /api/attributes/options/{id} @agent-pattern: Standard update */
    public function updateOption(int $optionId, array $data): array
    {
        $this->requireOption($optionId);
        $payload = $this->validator->validateOption($data, false);
        $this->repo->updateOption($optionId, $payload);
        return ['success' => true];
    }

    /** Delete option. @agent-use: DELETE /api/attributes/options/{id} @agent-pattern: Hard delete */
    public function deleteOption(int $optionId): array
    {
        $this->requireOption($optionId);
        $this->repo->deleteOption($optionId);
        return ['success' => true];
    }

    /** Create attribute value. @agent-use: POST /api/attribute-values @agent-pattern: Standard create */
    public function createValue(array $data): array
    {
        $payload = $this->validator->validateValueCreate($data);
        $this->requireAttribute((int) $payload['attribute_id']);
        $value = $this->repo->createValue($payload);
        return ['success' => true, 'data' => $value];
    }

    /** Delete attribute value. @agent-use: DELETE /api/attribute-values/{id} @agent-pattern: Hard delete */
    public function deleteValue(int $id): array
    {
        $this->repo->deleteValue($id);
        return ['success' => true];
    }

    /** Products by option. @agent-use: GET /api/attributes/options/{optionId}/products @agent-pattern: Read mapping */
    public function productsByOption(int $optionId): array
    {
        return ['success' => true, 'data' => $this->repo->productsByOption($optionId)];
    }

    /** Products by attribute. @agent-use: GET /api/attributes/{id}/products @agent-pattern: Read mapping */
    public function productsByAttribute(int $attributeId): array
    {
        $this->requireAttribute($attributeId);
        return ['success' => true, 'data' => $this->repo->productsByAttribute($attributeId)];
    }

    private function requireAttribute(int $id): array
    {
        $attr = $this->repo->findById($id);
        if (! $attr) { throw new RuntimeException('Attribute not found'); }
        return $attr;
    }

    private function requireOption(int $id): array
    {
        $option = $this->repo->findOption($id);
        if (! $option) { throw new RuntimeException('Option not found'); }
        return $option;
    }
}
