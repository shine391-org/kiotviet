<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\RoleModel;
use App\Models\PermissionModel;
use App\Models\RoleHasPermissionModel;
use CodeIgniter\API\ResponseTrait;

/**
 * @agent-controller: Roles management
 * @agent-pattern: Standard CRUD with permission assignment
 */
class RolesController extends BaseController
{
    use ResponseTrait;

    protected \CodeIgniter\Database\BaseConnection $db;
    protected RoleModel $roleModel;
    protected PermissionModel $permissionModel;
    protected RoleHasPermissionModel $roleHasPermissionModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->roleModel = new RoleModel();
        $this->permissionModel = new PermissionModel();
        $this->roleHasPermissionModel = new RoleHasPermissionModel();
    }

    public function index()
    {
        $rows = $this->db->table('roles')->where('deleted_at', null)->get()->getResultArray();
        return $this->respond(['success' => true, 'data' => $rows]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if (empty($data['name'])) {
            return $this->failValidationErrors('name required');
        }
        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->table('roles')->insert($payload);
        $payload['id'] = $this->db->insertID();
        return $this->respondCreated(['success' => true, 'data' => $payload]);
    }

    /**
     * Show a role by ID
     *
     * @agent-method: Get role by ID
     * @agent-pattern: Standard show method
     */
    public function show($id)
    {
        $role = $this->roleModel->find($id);
        if (!$role) {
            return $this->failNotFound('Role not found');
        }

        return $this->respond([
            'success' => true,
            'data' => $role
        ]);
    }

    /**
     * Update a role
     *
     * @agent-method: Update role
     * @agent-pattern: Standard update with validation
     */
    public function update($id)
    {
        $role = $this->roleModel->find($id);
        if (!$role) {
            return $this->failNotFound('Role not found');
        }

        $data = $this->request->getJSON(true);
        if (empty($data['name'])) {
            return $this->failValidationErrors('name required');
        }

        $payload = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->roleModel->update($id, $payload);
        
        return $this->respond([
            'success' => true,
            'data' => array_merge(['id' => $id], $payload)
        ]);
    }

    /**
     * Delete a role (soft delete)
     *
     * @agent-method: Delete role
     * @agent-pattern: Standard soft delete
     */
    public function delete($id)
    {
        $role = $this->roleModel->find($id);
        if (!$role) {
            return $this->failNotFound('Role not found');
        }

        // Check if it's a system role
        if ($role['is_system'] == 1) {
            return $this->fail('Cannot delete system role');
        }

        // Check if role is assigned to any users
        $userCount = $this->db->table('model_has_roles')
            ->where('role_id', $id)
            ->countAllResults();
        
        if ($userCount > 0) {
            return $this->fail('Cannot delete role that is assigned to users');
        }

        $this->roleModel->delete($id);
        
        return $this->respond([
            'success' => true,
            'message' => 'Role deleted successfully'
        ]);
    }

    /**
     * Get permissions for a specific role
     *
     * @agent-method: Get role permissions
     * @agent-pattern: Standard relationship query
     */
    public function getPermissions($roleId)
    {
        // Check if role exists
        $role = $this->roleModel->find($roleId);
        if (!$role) {
            return $this->failNotFound('Role not found');
        }

        // Get permissions for this role
        $permissions = $this->db->table('permissions p')
            ->select('p.id, p.name, p.display_name, p.module, p.module_group')
            ->join('role_has_permissions rp', 'rp.permission_id = p.id')
            ->where('rp.role_id', $roleId)
            ->where('p.deleted_at', null)
            ->get()
            ->getResultArray();

        return $this->respond([
            'success' => true,
            'data' => $permissions
        ]);
    }

    /**
     * Assign permissions to a role
     *
     * @agent-method: Assign permissions to role
     * @agent-pattern: Standard sync operation
     */
    public function assignPermissions($roleId)
    {
        // Check if role exists
        $role = $this->roleModel->find($roleId);
        if (!$role) {
            return $this->failNotFound('Role not found');
        }

        $data = $this->request->getJSON(true);
        if (empty($data['permission_ids']) || !is_array($data['permission_ids'])) {
            return $this->failValidationErrors('permission_ids array is required');
        }

        $permissionIds = $data['permission_ids'];
        
        // Validate all permission IDs exist
        if (!empty($permissionIds)) {
            $validPermissions = $this->permissionModel
                ->whereIn('id', $permissionIds)
                ->where('deleted_at', null)
                ->findAll();
            
            if (count($validPermissions) !== count($permissionIds)) {
                return $this->failValidationErrors('One or more permission IDs are invalid');
            }
        }

        // Start transaction
        $this->db->transStart();
        
        try {
            // Remove all existing permissions for this role
            $this->roleHasPermissionModel->where('role_id', $roleId)->delete();
            
            // Insert new permissions
            if (!empty($permissionIds)) {
                $insertData = [];
                foreach ($permissionIds as $permissionId) {
                    $insertData[] = [
                        'role_id' => $roleId,
                        'permission_id' => $permissionId
                    ];
                }
                $this->roleHasPermissionModel->insertBatch($insertData);
            }
            
            $this->db->transComplete();
            
            if ($this->db->transStatus() === false) {
                return $this->fail('Failed to assign permissions');
            }
            
            return $this->respond([
                'success' => true,
                'message' => 'Permissions assigned successfully'
            ]);
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            return $this->fail('Error: ' . $e->getMessage());
        }
    }
}
