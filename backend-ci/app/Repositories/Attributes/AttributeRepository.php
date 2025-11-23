<?php

namespace App\Repositories\Attributes;

use CodeIgniter\Database\BaseConnection;

/** Attribute DB operations. @agent-repository: Attribute persistence @agent-pattern: Repository pattern @agent-reusable: HIGH */
class AttributeRepository
{
    protected BaseConnection $db;
    public function __construct(?BaseConnection $db = null, string $group = 'default')
    {
        $this->db = $db ?? \Config\Database::connect($group);
    }

    /** List attributes with filters. @agent-use: Attribute listing @agent-pattern: Standard query */
    public function findAll(array $filters): array
    {
        $b = $this->db->table('product_attributes')->where('deleted_at', null);
        if (! empty($filters['search'])) { $b->groupStart()->like('name', $filters['search'])->orLike('attribute_key', $filters['search'])->groupEnd(); }
        if (! empty($filters['type'])) { $b->where('type', $filters['type']); }
        if (! empty($filters['status'])) { $b->where('status', $filters['status']); }
        return $b->orderBy('sort_order', 'ASC')->get()->getResultArray();
    }

    /** Get attribute by id. @agent-use: Fetch attribute @agent-pattern: Find by id */
    public function findById(int $id): ?array
    {
        $row = $this->db->table('product_attributes')->where('id', $id)->where('deleted_at', null)->get()->getRowArray();
        return $row ?: null;
    }

    /** Get option by id. @agent-use: Option operations @agent-pattern: Find by id */
    public function findOption(int $optionId): ?array
    {
        $row = $this->db->table('product_attribute_options')->where('id', $optionId)->get()->getRowArray();
        return $row ?: null;
    }

    /** Create attribute. @agent-use: Create flow @agent-pattern: Insert with timestamps */
    public function create(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->db->table('product_attributes')->insert($payload);
        $payload['id'] = $this->db->insertID();
        return $payload;
    }

    /** Update attribute. @agent-use: Update flow @agent-pattern: Update by id */
    public function update(int $id, array $data): bool
    {
        return (bool) $this->db->table('product_attributes')->where('id', $id)->update($data + ['updated_at' => $this->now()]);
    }

    /** Soft delete attribute. @agent-use: Delete flow @agent-pattern: Soft delete */
    public function delete(int $id): bool
    {
        return (bool) $this->db->table('product_attributes')->where('id', $id)->update(['deleted_at' => $this->now()]);
    }

    /** List options by attribute. @agent-use: Option listing @agent-pattern: Simple query */
    public function options(int $attributeId): array
    {
        return $this->db->table('product_attribute_options')->where('attribute_id', $attributeId)->orderBy('sort_order', 'ASC')->get()->getResultArray();
    }

    /** Create option. @agent-use: Create option @agent-pattern: Insert with timestamps */
    public function createOption(int $attributeId, array $data): array
    {
        $payload = $data + ['attribute_id' => $attributeId, 'created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->db->table('product_attribute_options')->insert($payload);
        $payload['id'] = $this->db->insertID();
        return $payload;
    }

    /** Update option. @agent-use: Update option @agent-pattern: Update by id */
    public function updateOption(int $optionId, array $data): bool
    {
        return (bool) $this->db->table('product_attribute_options')->where('id', $optionId)->update($data + ['updated_at' => $this->now()]);
    }

    /** Delete option. @agent-use: Delete option @agent-pattern: Hard delete */
    public function deleteOption(int $optionId): bool
    {
        return (bool) $this->db->table('product_attribute_options')->delete(['id' => $optionId]);
    }

    /** Create attribute value. @agent-use: Create value @agent-pattern: Insert with timestamps */
    public function createValue(array $data): array
    {
        $payload = $data + ['created_at' => $this->now(), 'updated_at' => $this->now()];
        $this->db->table('product_attribute_values')->insert($payload);
        $payload['id'] = $this->db->insertID();
        return $payload;
    }

    /** Delete value. @agent-use: Delete value @agent-pattern: Hard delete */
    public function deleteValue(int $id): bool
    {
        return (bool) $this->db->table('product_attribute_values')->delete(['id' => $id]);
    }

    /** Products/variants by option. @agent-use: Reporting @agent-pattern: Simple select */
    public function productsByOption(int $optionId): array
    {
        return $this->db->table('product_attribute_values pav')->select('pav.product_id, pav.variant_id')->where('pav.option_id', $optionId)->where('pav.deleted_at', null)->get()->getResultArray();
    }

    private function now(): string { return date('Y-m-d H:i:s'); }
}
