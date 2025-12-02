<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class BranchesController extends BaseController
{
    use ResponseTrait;

    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $rows = $this->db->table('branches')->where('deleted_at', null)->get()->getResultArray();
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
            'code' => $data['code'] ?? null,
            'address' => $data['address'] ?? null,
            'phone' => $data['phone'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
        $this->db->table('branches')->insert($payload);
        $payload['id'] = $this->db->insertID();
        return $this->respondCreated(['success' => true, 'data' => $payload]);
    }

    public function export()
    {
        $rows = $this->db->table('branches')->where('deleted_at', null)->get()->getResultArray();
        $csv = "id,name,code\n";
        foreach ($rows as $r) {
            $csv .= sprintf("%s,%s,%s\n", $r['id'], $r['name'], $r['code']);
        }
        return $this->response->setHeader('Content-Type', 'text/csv')->setBody($csv);
    }
}
