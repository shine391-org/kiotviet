<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\JwtService;
use App\Models\UserModel;
use App\Models\ModelHasRolesModel;
use App\Models\PermissionModel;
use App\Models\RoleModel;
use CodeIgniter\API\ResponseTrait;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthController extends BaseController
{
    use ResponseTrait;

    protected UserModel $users;
    protected ModelHasRolesModel $userRoles;
    protected PermissionModel $permissions;
    protected RoleModel $roles;
    protected JwtService $jwt;

    public function __construct()
    {
        $this->users       = new UserModel();
        $this->userRoles   = new ModelHasRolesModel();
        $this->permissions = new PermissionModel();
        $this->roles       = new RoleModel();
        $this->jwt         = new JwtService();
    }

    public function login()
    {
        $data = $this->request->getJSON(true);
        if (!$data || empty($data['username']) || empty($data['password'])) {
            return $this->fail(['message' => 'Username and password are required'], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $user = $this->users->where('username', $data['username'])->where('deleted_at', null)->first();
        if (!$user || !password_verify($data['password'], $user['password'])) {
            return $this->fail(['message' => 'Invalid credentials'], ResponseInterface::HTTP_UNAUTHORIZED);
        }

        if ($user['status'] !== 'active') {
            return $this->fail(['message' => 'Account is inactive'], ResponseInterface::HTTP_FORBIDDEN);
        }

        $roleRow = $this->userRoles
            ->where('model_type', 'App\\Models\\User')
            ->where('model_id', $user['id'])
            ->first();
        $roleName = null;
        if ($roleRow) {
            $role = $this->roles->find($roleRow['role_id']);
            $roleName = $role['name'] ?? null;
        }

        $perms = [];
        if ($roleRow) {
            $permRows = $this->permissions
                ->select('permissions.id, permissions.name, permissions.display_name, permissions.module, permissions.module_group')
                ->join('role_has_permissions', 'permissions.id = role_has_permissions.permission_id')
                ->where('role_has_permissions.role_id', $roleRow['role_id'])
                ->where('permissions.deleted_at', null)
                ->findAll();
            $perms = $permRows;
        }

        $token = $this->jwt->generateToken([
            'id'        => (string) $user['id'],
            'username'  => $user['username'],
            'role'      => $roleName ?? '',
            'branch_id' => $user['branch_id'] ?? null,
        ]);

        // update last login
        $this->users->update($user['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => $this->request->getIPAddress(),
        ]);

        return $this->respond([
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => [
                'id'         => (string) $user['id'],
                'username'   => $user['username'],
                'full_name'  => $user['full_name'],
                'email'      => $user['email'],
                'role'       => $roleName,
                'branch_id'  => (string) ($user['branch_id'] ?? ''),
                'permissions'=> $perms,
            ],
        ]);
    }
}
