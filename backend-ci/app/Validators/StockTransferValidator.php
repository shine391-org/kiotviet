<?php

namespace App\Validators;

use InvalidArgumentException;

class StockTransferValidator
{
    public function validateListFilters(array $input): array
    {
        return [
            'search' => trim($input['search'] ?? ''),
            'page' => max(1, (int) ($input['page'] ?? 1)),
            'limit' => min(100, max(1, (int) ($input['limit'] ?? 15))),
            'sort' => $input['sort'] ?? 'transfer_date,desc',
            'statuses' => $this->parseArray($input['statuses'] ?? null),
            'fromBranches' => $this->parseArray($input['fromBranches'] ?? null),
            'toBranches' => $this->parseArray($input['toBranches'] ?? null),
            'transferDateEnabled' => filter_var($input['transferDateEnabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'receiveDateEnabled' => filter_var($input['receiveDateEnabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'dateMode' => $input['dateMode'] ?? 'this_year',
            'customFrom' => $input['customFrom'] ?? null,
            'customTo' => $input['customTo'] ?? null,
        ];
    }

    public function validateCreate(array $input): array
    {
        if (empty($input['from_branch_id'])) {
            throw new InvalidArgumentException('Chi nhánh chuyển là bắt buộc');
        }

        if (empty($input['to_branch_id'])) {
            throw new InvalidArgumentException('Chi nhánh nhận là bắt buộc');
        }

        if ($input['from_branch_id'] == $input['to_branch_id']) {
            throw new InvalidArgumentException('Chi nhánh chuyển và nhận không được trùng nhau');
        }

        return [
            'from_branch_id' => (int) $input['from_branch_id'],
            'to_branch_id' => (int) $input['to_branch_id'],
            'transfer_date' => $input['transfer_date'] ?? null,
            'notes' => $input['notes'] ?? null,
            'items' => $input['items'] ?? [],
            'created_by' => $input['created_by'] ?? null,
        ];
    }

    public function validateUpdate(array $input): array
    {
        $data = [];

        if (isset($input['to_branch_id'])) {
            $data['to_branch_id'] = (int) $input['to_branch_id'];
        }

        if (isset($input['notes'])) {
            $data['notes'] = $input['notes'];
        }

        if (isset($input['transfer_date'])) {
            $data['transfer_date'] = $input['transfer_date'];
        }

        if (isset($input['items'])) {
            $data['items'] = $input['items'];
        }

        return $data;
    }

    protected function parseArray($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value) && $value !== '') {
            return explode(',', $value);
        }
        return [];
    }
}
