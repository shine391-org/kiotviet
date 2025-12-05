<?php

namespace App\Controllers\Api;

use CodeIgniter\RESTful\ResourceController;

class HealthController extends ResourceController
{
    public function index()
    {
        return $this->respond(['status' => 'ok', 'time' => time()]);
    }
}
