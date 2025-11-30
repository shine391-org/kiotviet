<?php

namespace App\Services\HR;

use App\Repositories\HR\EmployeeRepository;
use App\Validators\EmployeeValidator;
use RuntimeException;

/**
 * Employee service.
 *
 * @agent-service: Employee
 * @agent-pattern: CRUD service
 * @agent-reusable: MEDIUM
 */
class EmployeeService
{
    protected EmployeeRepository $repo;
    protected EmployeeValidator $validator;

    public function __construct(?EmployeeRepository $repo = null, ?EmployeeValidator $validator = null)
    {
        $this->repo = $repo ?? new EmployeeRepository();
        $this->validator = $validator ?? new EmployeeValidator();
    }

    /** @agent-use: GET /api/employees */
    public function list(array $filters): array
    {
        return ['success' => true, 'data' => $this->repo->list($filters)];
    }

    /** @agent-use: GET /api/employees/{id} */
    public function show(int $id): array
    {
        return ['success' => true, 'data' => $this->require($id)];
    }

    /** @agent-use: POST /api/employees */
    public function create(array $input): array
    {
        $data = $this->validator->validateCreate($input);
        $code = $this->repo->nextCode();
        $created = $this->repo->create($data + ['employee_code' => $code]);
        return ['success' => true, 'data' => $created];
    }

    /** @agent-use: PUT /api/employees/{id} */
    public function update(int $id, array $input): array
    {
        $this->require($id);
        $data = $this->validator->validateUpdate($input);
        $this->repo->update($id, $data);
        return ['success' => true, 'data' => $this->require($id)];
    }

    private function require(int $id): array
    {
        $row = $this->repo->find($id);
        if (! $row) {
            throw new RuntimeException('Employee not found');
        }
        return $row;
    }
}
