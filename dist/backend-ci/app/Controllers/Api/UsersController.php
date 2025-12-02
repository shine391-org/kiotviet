<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\ModelHasRolesModel;
use App\Models\PermissionModel;
use CodeIgniter\API\ResponseTrait;

class UsersController extends BaseController
{
    use ResponseTrait;

    protected UserModel $users;
    protected RoleModel $roles;
    protected ModelHasRolesModel $userRoles;
    protected PermissionModel $permissions;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->users = new UserModel();
        $this->roles = new RoleModel();
        $this->userRoles = new ModelHasRolesModel();
        $this->permissions = new PermissionModel();
    }

    public function me()
    {
        // Dev mode: lấy user từ header X-User hoặc query ?username=, fallback admin/devadmin
        $username = $this->request->getHeaderLine('X-User')
            ?: ($this->request->getGet('username') ?? null)
            ?: 'admin';

        $user = $this->users->where('username', $username)->where('deleted_at', null)->first();
        if (! $user && $username !== 'devadmin') {
            $user = $this->users->where('username', 'devadmin')->where('deleted_at', null)->first();
        }
        if (! $user) {
            return $this->failNotFound('User not found');
        }

        // Lấy role
        $roleRow = $this->userRoles
            ->where('model_id', $user['id'])
            ->first();
        $roleName = null;
        if ($roleRow) {
            $role = $this->roles->find($roleRow['role_id']);
            $roleName = $role['name'] ?? null;
        }

        // Lấy permissions theo role (để FE bật nút chỉnh sửa)
        $perms = [];
        if ($roleRow) {
            $db = \Config\Database::connect();
            $perms = $db->table('permissions p')
                ->select('p.id, p.name, p.display_name, p.module, p.module_group')
                ->join('role_has_permissions rp', 'rp.permission_id = p.id')
                ->where('rp.role_id', $roleRow['role_id'])
                ->where('p.deleted_at', null)
                ->get()->getResultArray();
            $perms = $this->withAliases($perms);
        }

        return $this->respond([
            'user' => [
                'id' => (string) $user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'branch_id' => (string) ($user['branch_id'] ?? ''),
                'role' => $roleName,
                'permissions' => $perms,
            ],
        ]);
    }

    /** Thêm alias quyền để tương thích FE cũ (products.edit, products.manage). */
    private function withAliases(array $perms): array
    {
        $names = array_column($perms, 'name');
        $aliasMap = [
            'products.edit' => 'products.update',
            'products.manage' => 'products.manage_variants',
        ];
        foreach ($aliasMap as $alias => $source) {
            if (!in_array($alias, $names, true) && in_array($source, $names, true)) {
                foreach ($perms as $p) {
                    if ($p['name'] === $source) {
                        $clone = $p;
                        $clone['name'] = $alias;
                        $clone['display_name'] = $alias;
                        $perms[] = $clone;
                        $names[] = $alias;
                        break;
                    }
                }
            }
        }
        return $perms;
    }

    public function index()
    {
        $data = $this->users->where('deleted_at', null)->findAll();
        return $this->respond([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function show($id)
    {
        $user = $this->users->find($id);
        if (!$user) {
            return $this->failNotFound('User not found');
        }
        return $this->respond(['success' => true, 'data' => $user]);
    }

    public function create()
    {
        $data = $this->request->getJSON(true);
        if (empty($data['username']) || empty($data['password'])) {
            return $this->failValidationErrors('username và password bắt buộc');
        }
        
        // Extract role_id before creating user
        $roleId = $data['role_id'] ?? null;
        
        // Prepare user payload
        $payload = $data;
        $payload['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        
        // Remove role_id from user payload (it goes to separate table)
        unset($payload['role_id']);
        
        // Start transaction
        $this->db->transStart();
        
        try {
            // Create user
            $this->users->insert($payload);
            $userId = $this->users->getInsertID();
            
            // Assign role if provided
            if ($roleId) {
                // Validate role exists
                $role = $this->roles->find($roleId);
                if (!$role) {
                    $this->db->transRollback();
                    return $this->failValidationErrors('Invalid role_id');
                }
                
                // Assign role to user
                $this->userRoles->insert([
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $userId
                ]);
            }
            
            $this->db->transComplete();
            
            if ($this->db->transStatus() === false) {
                return $this->fail('Failed to create user');
            }
            
            return $this->respondCreated([
                'success' => true,
                'data' => ['id' => $userId] + $payload
            ]);
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            return $this->fail('Error: ' . $e->getMessage());
        }
    }

    public function update($id)
    {
        $data = $this->request->getJSON(true);
        $user = $this->users->find($id);
        
        if (!$user) {
            return $this->failNotFound('User not found');
        }
        
        // Extract role_id before updating user
        $roleId = $data['role_id'] ?? null;
        
        // Prepare user payload
        $payload = $data;
        
        // Handle password if provided
        if (!empty($data['password'])) {
            $payload['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        
        $payload['updated_at'] = date('Y-m-d H:i:s');
        
        // Remove role_id from user payload (it goes to separate table)
        unset($payload['role_id']);
        
        // Start transaction
        $this->db->transStart();
        
        try {
            // Update user
            $this->users->update($id, $payload);
            
            // Update role assignment if provided
            if ($roleId !== null) {
                // Remove existing role assignment
                $this->userRoles->where('model_id', $id)->delete();
                
                // Assign new role if not empty
                if (!empty($roleId)) {
                    // Validate role exists
                    $role = $this->roles->find($roleId);
                    if (!$role) {
                        $this->db->transRollback();
                        return $this->failValidationErrors('Invalid role_id');
                    }
                    
                    // Assign new role to user
                    $this->userRoles->insert([
                        'role_id' => $roleId,
                        'model_type' => 'App\\Models\\User',
                        'model_id' => $id
                    ]);
                }
            }
            
            $this->db->transComplete();
            
            if ($this->db->transStatus() === false) {
                return $this->fail('Failed to update user');
            }
            
            return $this->respond(['success' => true]);
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            return $this->fail('Error: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        if (!$this->users->find($id)) {
            return $this->failNotFound('User not found');
        }
        $this->users->delete($id);
        return $this->respond(['success' => true]);
    }

    public function changePassword($id)
    {
        $data = $this->request->getJSON(true);
        if (empty($data['password'])) {
            return $this->failValidationErrors('password required');
        }
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $this->users->update($id, ['password' => $hash, 'updated_at' => date('Y-m-d H:i:s')]);
        return $this->respond(['success' => true]);
    }

    public function roles()
    {
        $data = $this->roles->where('deleted_at', null)->findAll();
        return $this->respond(['success' => true, 'data' => $data]);
    }

    public function branches()
    {
        $db = \Config\Database::connect();
        $rows = $db->table('branches')->where('deleted_at', null)->get()->getResultArray();
        return $this->respond(['success' => true, 'data' => $rows]);
    }
}
