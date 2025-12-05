<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Repositories\HR\SalarySlipRepository;
use CodeIgniter\API\ResponseTrait;

/**
 * Salary slip API.
 *
 * @agent-controller: SalarySlips
 * @agent-pattern: Thin controller
 */
class SalarySlipsController extends BaseController
{
    use ResponseTrait;

    protected SalarySlipRepository $repo;

    public function __construct()
    {
        $this->repo = new SalarySlipRepository();
    }

    public function show($id)
    {
        return $this->wrap(function () use ($id) {
            $slip = $this->repo->find((int) $id);
            if (! $slip) {
                return $this->failNotFound('Salary slip not found');
            }
            return $this->respond(['success' => true, 'data' => $slip]);
        });
    }

    private function wrap(callable $action)
    {
        try {
            return $action();
        } catch (\Throwable $e) {
            return $this->failServerError($e->getMessage());
        }
    }
}
