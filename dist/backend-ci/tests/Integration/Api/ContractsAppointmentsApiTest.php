<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: Contract + appointment API integration
 * @agent-pattern: FeatureTestTrait + DevDatabaseTrait
 */
class ContractsAppointmentsApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use DevDatabaseTrait;
    use AuthTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $config = config('Database');
        $config->tests['database'] = 'lanocrm_shop';
        require_once APPPATH . 'Database/Migrations/2025-11-21-000000_TestSchemaSetup.php';
        (new \App\Database\Migrations\TestSchemaSetup())->up();

        $this->setUpDatabase();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_contract_and_manages_appointments()
    {
        $today = date('Y-m-d');
        $tplRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode(['name' => 'Standard', 'terms' => 'Base']))
            ->post('/api/contract-templates');
        $tplRes->assertStatus(201);
        $template = $this->getJsonFromResponse($tplRes)['data'];

        $contractRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'customer_id' => 1,
                'template_id' => $template['id'],
                'terms' => [['description' => 'Deliver sample']],
            ]))
            ->post('/api/contracts');
        $contractRes->assertStatus(201);
        $contract = $this->getJsonFromResponse($contractRes)['data'];

        $activateRes = $this->withHeaders($this->jsonHeaders())
            ->post('/api/contracts/' . $contract['id'] . '/activate');
        $activateRes->assertStatus(200);
        $this->assertEquals('active', $this->getJsonFromResponse($activateRes)['data']['status']);

        $start = $today . ' 09:00:00';
        $end = $today . ' 10:00:00';
        $apptRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'customer_id' => 1,
                'contract_id' => $contract['id'],
                'start_time' => $start,
                'end_time' => $end,
                'notes' => 'Kickoff',
            ]))
            ->post('/api/appointments');
        $apptRes->assertStatus(201);
        $appointment = $this->getJsonFromResponse($apptRes)['data'];

        $rescheduleRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'start_time' => $today . ' 11:00:00',
                'end_time' => $today . ' 12:00:00',
                'status' => 'scheduled',
            ]))
            ->post('/api/appointments/' . $appointment['id'] . '/reschedule');
        $rescheduleRes->assertStatus(200);
        $this->assertEquals(
            date('Y-m-d') . ' 11:00:00',
            $this->getJsonFromResponse($rescheduleRes)['data']['start_time']
        );

        $conflictRes = $this->withHeaders($this->jsonHeaders())
            ->withBody(json_encode([
                'start_time' => $today . ' 11:30:00',
                'end_time' => $today . ' 12:30:00',
            ]))
            ->post('/api/appointments');
        $conflictRes->assertStatus(400);
    }

    private function getJsonFromResponse($response): array
    {
        $raw = $response->getBody();
        $jsonString = $raw;
        if (strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\/p>/s', $raw, $m)) {
            $jsonString = html_entity_decode($m[1]);
        }
        return json_decode($jsonString, true);
    }

    private function jsonHeaders(): array
    {
        return $this->authHeaders(['Content-Type' => 'application/json']);
    }
}
