<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class RolesController extends BaseController
{
    use ResponseTrait;

    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
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
}
