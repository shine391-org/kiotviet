<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Payroll API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class PayrollApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();

        $this->setUpDatabase();
        $this->db->table('gl_entries')->truncate();
        $this->db->table('employees')->truncate();
        $this->db->table('chart_of_accounts')->truncate();
        $this->db->table('payroll_runs')->truncate();
        $this->db->table('payroll_slips')->truncate();
        $this->setUpAuthToken();
        $this->seedBase();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_runs_payroll_via_api()
    {
        $empRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['full_name' => 'Dana']))
            ->post('/api/employees');
        $empRes->assertStatus(201);
        $emp = $this->getJsonFromResponse($empRes)['data'];

        $payRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'period_start' => '2025-12-01',
                'period_end' => '2025-12-31',
                'employees' => [
                    [
                        'employee_id' => $emp['id'],
                        'earnings' => [['component_name' => 'Basic', 'amount' => 1000]],
                        'deductions' => [['component_name' => 'Tax', 'amount' => 100]],
                    ],
                ],
                'expense_account_id' => 1,
                'payable_account_id' => 2,
            ]))
            ->post('/api/payroll');
        $payRes->assertStatus(201);

        $glCount = $this->db->table('gl_entries')->countAllResults();
        $this->assertEquals(2, $glCount);
    }

    private function seedBase(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('chart_of_accounts')->insert(['id' => 1, 'name' => 'Salary Expense', 'account_type' => 'expense', 'created_at' => $now]);
        $this->db->table('chart_of_accounts')->insert(['id' => 2, 'name' => 'Salary Payable', 'account_type' => 'liability', 'created_at' => $now]);
    }

    private function getJsonFromResponse($response): array
    {
        $raw = $response->getBody();
        $jsonString = $raw;
        if (strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\\/p>/s', $raw, $m)) {
            $jsonString = html_entity_decode($m[1]);
        }
        return json_decode($jsonString, true);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }
}
