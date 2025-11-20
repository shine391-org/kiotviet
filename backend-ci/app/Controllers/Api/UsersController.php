<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\RoleModel;
use CodeIgniter\API\ResponseTrait;

class UsersController extends BaseController
{
    use ResponseTrait;

    protected UserModel $users;
    protected RoleModel $roles;

    public function __construct()
    {
        $this->users = new UserModel();
        $this->roles = new RoleModel();
    }

    public function me()
    {
        // For demo, pick first user by token verification already done elsewhere
        // Here we simply return devadmin as current user
        $user = $this->users->where('username', 'devadmin')->first();
        if (!$user) {
            return $this->failNotFound();
        }
        return $this->respond([
            'user' => [
                'id' => (string)$user['id'],
                'username' => $user['username'],
                'full_name' => $user['full_name'],
                'email' => $user['email'],
                'branch_id' => (string) ($user['branch_id'] ?? ''),
                'permissions' => [],
            ],
        ]);
    }

    public function index()
    {
        $data = $this->users->where('deleted_at', null)->findAll();
        return $this->respond([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function roles()
    {
        $data = $this->roles->where('deleted_at', null)->findAll();
        return $this->respond(['success' => true, 'data' => $data]);
    }

    public function branches()
    {
        // Static demo branch
        return $this->respond([
            'success' => true,
            'data' => [
                ['id' => 1, 'name' => 'Main Branch'],
            ]
        ]);
    }
}
