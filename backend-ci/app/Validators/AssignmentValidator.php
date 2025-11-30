<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate assignment rules.
 *
 * @agent-validator: Assignment
 * @agent-pattern: Validation first
 * @agent-reusable: MEDIUM
 */
class AssignmentValidator
{
    protected Validation $v;

    public function __construct(?Validation $v = null)
    {
        $this->v = $v ?? Services::validation(null, false);
    }

    public function validateRule(array $input): array
    {
        $data = $this->run($input, [
            'name' => 'required|string|max_length[150]',
            'entity_type' => 'required|string|max_length[80]',
            'strategy' => 'permit_empty|string|max_length[50]',
            'team_members' => 'required',
        ]);
        $data['strategy'] = $data['strategy'] ?? 'round_robin';
        $members = $this->normalizeMembers($input['team_members'] ?? []);
        if (empty($members)) {
            throw new InvalidArgumentException('team_members required');
        }
        $data['team_members'] = $members;
        return $data;
    }

    public function validateAssign(array $input): array
    {
        return $this->run($input, [
            'entity_type' => 'required|string|max_length[80]',
            'entity_id' => 'required|integer|greater_than[0]',
        ]);
    }

    private function normalizeMembers($members): array
    {
        if (! is_array($members)) {
            return [];
        }
        $clean = [];
        foreach ($members as $member) {
            $id = (int) $member;
            if ($id > 0) {
                $clean[] = $id;
            }
        }
        return $clean;
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->v->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->v->getErrors())) ?: 'Invalid data');
        }
        return $this->v->getValidated();
    }
}
