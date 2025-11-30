<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Leaves API
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class LeavesApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    private int $employeeId;
    private int $leaveTypeId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->setUpAuthToken();
        $this->seedEmployee();
        $this->seedLeaveType();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_applies_approves_and_checks_balance()
    {
        $apply = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'employee_id' => $this->employeeId,
                'leave_type_id' => $this->leaveTypeId,
                'from_date' => date('Y-m-d'),
                'to_date' => date('Y-m-d'),
                'reason' => 'Annual leave test',
            ]))
            ->post('/api/leaves/apply');
        $apply->assertStatus(201);
        $leave = $this->decode($apply)['data'];
        $this->assertEquals('pending', $leave['status']);

        $approve = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['approved_by' => 9]))
            ->post('/api/leaves/' . $leave['id'] . '/approve');
        $approve->assertStatus(200);
        $approved = $this->decode($approve)['data'];
        $this->assertEquals('approved', $approved['status']);

        $balanceRes = $this->withHeaders($this->jsonHeaders())
            ->get('/api/leaves/balance?employee_id=' . $this->employeeId . '&leave_type_id=' . $this->leaveTypeId);
        $balanceRes->assertStatus(200);
        $balancePayload = $this->decode($balanceRes);
        $this->assertEquals(4.0, (float) $balancePayload['data']['balance']);

        $list = $this->withHeaders($this->jsonHeaders())
            ->get('/api/leaves?employee_id=' . $this->employeeId);
        $list->assertStatus(200);
        $listPayload = $this->decode($list);
        $this->assertNotEmpty($listPayload['data']);
    }

    private function seedEmployee(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('employees')->insert([
            'employee_code' => 'EMP-001',
            'full_name' => 'Test Employee',
            'branch_id' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->employeeId = (int) $this->db->insertID();
    }

    private function seedLeaveType(): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->table('leave_types')->insert([
            'leave_name' => 'Annual',
            'default_allocation' => 5,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->leaveTypeId = (int) $this->db->insertID();
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }

    private function decode($response): array
    {
        $raw = $response->getBody();
        if (strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\\/p>/s', $raw, $m)) {
            $raw = html_entity_decode($m[1]);
        }
        return json_decode($raw, true);
    }
}
