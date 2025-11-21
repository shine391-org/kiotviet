<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/** Attribute module validation @agent-validator: Attribute validation @agent-pattern: Validation first @agent-reusable: HIGH */
class AttributeValidator
{
    protected Validation $v; public function __construct(?Validation $v = null) { $this->v = $v ?? Services::validation(); }

    /** Validate list filters. @agent-use: GET /api/attributes @agent-pattern: Standard list validation */
    public function validateList(array $input): array { return $this->run($input, ['search' => 'permit_empty|string|max_length[255]', 'type' => 'permit_empty|string|max_length[50]', 'status' => 'permit_empty|string|max_length[50]']); }

    /** Validate attribute creation. @agent-use: POST /api/attributes @agent-pattern: Standard create validation */
    public function validateAttributeCreate(array $input): array
    { $rules = ['name' => 'required|string|max_length[255]', 'slug' => 'permit_empty|string|max_length[255]', 'attribute_key' => 'permit_empty|string|max_length[100]', 'type' => 'permit_empty|string|max_length[50]', 'is_required' => 'permit_empty|in_list[0,1,true,false]', 'is_filterable' => 'permit_empty|in_list[0,1,true,false]', 'is_visible' => 'permit_empty|in_list[0,1,true,false]', 'sort_order' => 'permit_empty|integer', 'status' => 'permit_empty|string|max_length[50]']; return $this->normalizeBools($this->run($input, $rules), ['is_required','is_filterable','is_visible']); }

    /** Validate attribute update. @agent-use: PUT /api/attributes/{id} @agent-pattern: Standard update validation */
    public function validateAttributeUpdate(array $input): array
    { $validated = $this->normalizeBools($this->run($input, ['name' => 'permit_empty|string|max_length[255]', 'slug' => 'permit_empty|string|max_length[255]', 'attribute_key' => 'permit_empty|string|max_length[100]', 'type' => 'permit_empty|string|max_length[50]', 'is_required' => 'permit_empty|in_list[0,1,true,false]', 'is_filterable' => 'permit_empty|in_list[0,1,true,false]', 'is_visible' => 'permit_empty|in_list[0,1,true,false]', 'sort_order' => 'permit_empty|integer', 'status' => 'permit_empty|string|max_length[50]']), ['is_required','is_filterable','is_visible']); if (empty($validated)) { throw new InvalidArgumentException('No data to update'); } return $validated; }

    /** Validate option create/update. @agent-use: Option CRUD @agent-pattern: Reusable option validation */
    public function validateOption(array $input, bool $isCreate = false): array
    { $rules = ['option_name' => ($isCreate ? 'required' : 'permit_empty') . '|string|max_length[255]', 'option_value' => 'permit_empty|string|max_length[255]', 'color_code' => 'permit_empty|string|max_length[50]', 'image_url' => 'permit_empty|string|max_length[255]', 'sort_order' => 'permit_empty|integer', 'status' => 'permit_empty|string|max_length[50]']; $validated = $this->run($input, $rules); if (!$isCreate && empty($validated)) { throw new InvalidArgumentException('No data to update'); } return $validated; }

    /** Validate attribute value create. @agent-use: POST /api/attribute-values @agent-pattern: Standard create validation */
    public function validateValueCreate(array $input): array { return $this->run($input, ['attribute_id' => 'required|integer|greater_than[0]', 'product_id' => 'permit_empty|integer|greater_than[0]', 'variant_id' => 'permit_empty|integer|greater_than[0]', 'option_id' => 'permit_empty|integer|greater_than[0]', 'value_text' => 'permit_empty|string']); }

    private function run(array $data, array $rules): array { if (! $this->v->setRules($rules)->run($data)) { throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data'); } return $this->v->getValidated(); }
    private function normalizeBools(array $data, array $fields): array { foreach ($fields as $f) { if (array_key_exists($f, $data)) { $data[$f] = filter_var($data[$f], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE); } } return $data; }
}
