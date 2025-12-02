<?php

namespace App\Validators;

use CodeIgniter\Validation\Validation;
use Config\Services;
use InvalidArgumentException;

/**
 * Validate filters for the dashboard endpoints.
 *
 * @agent-validator: Dashboard filters
 * @agent-pattern: Centralized filter validation
 * @agent-reusable: HIGH
 */
class DashboardValidator
{
    protected Validation $validation;

    public function __construct(?Validation $validation = null)
    {
        $this->validation = $validation ?? Services::validation(null, false);
    }

    /**
     * @agent-use: GET /api/dashboard/kpi-today
     * @agent-pattern: Simple branch filter validation
     */
    public function validateKpi(array $input): array
    {
        return $this->run($input, [
            'branch_id' => 'permit_empty|integer|greater_than_equal_to[0]',
        ]);
    }

    /**
     * @agent-use: GET /api/dashboard/revenue-chart
     * @agent-pattern: Chart filter validation with range options
     */
    public function validateChartFilters(array $input): array
    {
        $data = $this->run($input, [
            'period' => 'permit_empty|in_list[day,hour,weekday]',
            'range' => 'permit_empty|in_list[today,week,month,custom]',
            'chart_type' => 'permit_empty|in_list[column,bar]',
            'branch_id' => 'permit_empty|integer|greater_than_equal_to[0]',
            'from_date' => 'permit_empty|valid_date[Y-m-d]',
            'to_date' => 'permit_empty|valid_date[Y-m-d]',
        ]);

        if (($data['range'] ?? 'month') === 'custom' && (empty($data['from_date']) || empty($data['to_date']))) {
            throw new InvalidArgumentException('Custom range requires from_date and to_date');
        }

        return $data;
    }

    /**
     * @agent-use: GET /api/dashboard/top-products
     * @agent-pattern: Sorting + pagination filters
     */
    public function validateTopProductsFilters(array $input): array
    {
        return $this->run($input, [
            'metric' => 'permit_empty|in_list[net_revenue,revenue,quantity,profit_margin]',
            'range' => 'permit_empty|in_list[today,week,month,custom]',
            'limit' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[50]',
            'from_date' => 'permit_empty|valid_date[Y-m-d]',
            'to_date' => 'permit_empty|valid_date[Y-m-d]',
        ]);
    }

    /**
     * @agent-use: GET /api/dashboard/top-customers
     * @agent-pattern: Range + limit guards
     */
    public function validateTopCustomersFilters(array $input): array
    {
        return $this->run($input, [
            'range' => 'permit_empty|in_list[today,week,month,custom]',
            'limit' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[50]',
            'from_date' => 'permit_empty|valid_date[Y-m-d]',
            'to_date' => 'permit_empty|valid_date[Y-m-d]',
        ]);
    }

    /**
     * @agent-use: GET /api/dashboard/activities
     * @agent-pattern: Limit guard for activity feed
     */
    public function validateActivitiesFilters(array $input): array
    {
        return $this->run($input, [
            'limit' => 'permit_empty|integer|greater_than[0]|less_than_equal_to[50]',
        ]);
    }

    private function run(array $data, array $rules): array
    {
        if (! $this->validation->setRules($rules)->run($data)) {
            throw new InvalidArgumentException(implode('; ', array_filter($this->validation->getErrors())) ?: 'Invalid data');
        }
        return $this->validation->getValidated();
    }
}
