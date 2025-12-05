<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Model for role_has_permissions table
 *
 * @agent-model: Role-Permission mapping
 * @agent-pattern: Standard pivot table model
 * @agent-reusable: HIGH
 */
class RoleHasPermissionModel extends Model
{
    protected $table = 'role_has_permissions';
    protected $primaryKey = ['permission_id', 'role_id']; // Composite primary key
    protected $returnType = 'array';
    protected $allowedFields = ['permission_id', 'role_id'];
    public $incrementing = false;
    protected $useAutoIncrement = false;
    protected $useTimestamps = false;
    
    /**
     * Override insertBatch to handle composite primary key
     */
    public function insertBatch(?array $set = null, ?bool $escape = null, int $batchSize = 100, bool $testing = false)
    {
        if ($set === null) {
            return parent::insertBatch($set, $escape, $batchSize, $testing);
        }
        return $this->db->table($this->table)->insertBatch($set);
    }
}