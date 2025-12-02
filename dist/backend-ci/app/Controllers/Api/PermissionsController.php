<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;

class PermissionsController extends BaseController
{
    use ResponseTrait;

    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $rows = $this->db->table('permissions')->get()->getResultArray();
        return $this->respond(['success' => true, 'data' => $rows]);
    }
}
