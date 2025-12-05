<?php

namespace App\Libraries;

use Config\PolymorphicTypes;
use InvalidArgumentException;

/**
 * Validator cho các reference/entity đa hình.
 *
 * @agent-library: Polymorphic validation
 * @agent-pattern: Allow-list + optional DB existence check
 */
class PolymorphicValidator
{
    protected PolymorphicTypes $config;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct(?PolymorphicTypes $config = null, ?\CodeIgniter\Database\BaseConnection $db = null)
    {
        $this->config = $config ?? config(PolymorphicTypes::class);
        $this->db = $db ?? db_connect();
    }

    /**
     * Kiểm tra type + id theo context.
     *
     * @param string $context       Tên context (key trong PolymorphicTypes::$contexts)
     * @param string|null $type     Giá trị entity_type/reference_type
     * @param int|string|null $id   ID tham chiếu (tuỳ chọn)
     *
     * @throws InvalidArgumentException nếu không hợp lệ
     */
    public function validate(string $context, ?string $type, $id = null): void
    {
        $type = trim((string) $type);
        if ($type === '') {
            throw new InvalidArgumentException('type is required');
        }

        $map = $this->config->contexts[$context] ?? null;
        if ($map === null) {
            throw new InvalidArgumentException("Unknown context: {$context}");
        }

        $rule = $map[$type] ?? null;
        if ($rule === null) {
            throw new InvalidArgumentException("Unsupported type '{$type}' for context '{$context}'");
        }

        $table = $rule['table'] ?? null;
        if ($table && $id !== null && $id !== '') {
            $exists = $this->db->table($table)->where('id', $id)->limit(1)->get()->getRowArray();
            if (! $exists) {
                throw new InvalidArgumentException("Referenced ID {$id} not found in {$table} for type {$type}");
            }
        }
    }
}
