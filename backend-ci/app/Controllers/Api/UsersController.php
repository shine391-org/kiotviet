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
        $payload = $data;
        $payload['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $payload['created_at'] = date('Y-m-d H:i:s');
        $payload['updated_at'] = date('Y-m-d H:i:s');
        $this->users->insert($payload);
        $id = $this->users->getInsertID();
        return $this->respondCreated(['success' => true, 'data' => ['id' => $id] + $payload]);
    }

    public function update($id)
    {
        $data = $this->request->getJSON(true);
        if (!$this->users->find($id)) {
            return $this->failNotFound('User not found');
        }
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        }
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->users->update($id, $data);
        return $this->respond(['success' => true]);
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
