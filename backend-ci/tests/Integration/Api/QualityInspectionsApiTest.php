<?php

namespace Tests\Integration\Api;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Tests\Support\AuthTestTrait;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\QualitySchemaTrait;

/**
 * @agent-test: Quality inspections API
 * @agent-pattern: Integration test with DevDatabaseTrait + FeatureTestTrait
 */
class QualityInspectionsApiTest extends CIUnitTestCase
{
    use FeatureTestTrait;
    use AuthTestTrait;
    use DevDatabaseTrait;
    use QualitySchemaTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetQualitySchema();
        $this->setUpAuthToken();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_creates_submits_and_approves_inspection_for_delivery_context(): void
    {
        $headers = $this->authHeaders(['Content-Type' => 'application/json']);

        $paramResponse = $this->withHeaders($headers)->withBody(json_encode([
            'name' => 'Damage Check',
            'uom' => '%',
            'min_value' => 0,
            'max_value' => 5,
        ]))->post('/api/quality-parameters');
        $paramBody = $this->decode($paramResponse);
        $this->assertTrue($paramBody['success'] ?? false, json_encode($paramBody));
        $parameterId = $paramBody['data']['id'];

        $createResponse = $this->withHeaders($headers)->withBody(json_encode([
            'reference_type' => 'delivery_note',
            'reference_id' => 44,
            'inspected_by' => 7,
            'items' => [
                ['parameter_id' => $parameterId, 'value_numeric' => 2.5, 'notes' => 'Front pallet'],
            ],
        ]))->post('/api/quality-inspections');
        $createBody = $this->decode($createResponse);
        $this->assertTrue($createBody['success'] ?? false, json_encode($createBody));
        $inspectionId = $createBody['data']['id'];

        $submitResponse = $this->withHeaders($headers)->post('/api/quality-inspections/' . $inspectionId . '/submit');
        $submitBody = $this->decode($submitResponse);
        $this->assertTrue($submitBody['success'] ?? false, json_encode($submitBody));
        $this->assertSame('submitted', $submitBody['data']['status']);
        $this->assertSame('pass', $submitBody['data']['result']);

        $approveResponse = $this->withHeaders($headers)
            ->withBody(json_encode(['approved_by' => 7]))
            ->post('/api/quality-inspections/' . $inspectionId . '/approve');
        $approveBody = $this->decode($approveResponse);
        $this->assertTrue($approveBody['success'] ?? false, json_encode($approveBody));
        $this->assertSame('approved', $approveBody['data']['status']);

        $row = $this->db->table('quality_inspection_items')->where('inspection_id', $inspectionId)->get()->getRowArray();
        $this->assertSame('1', (string) $row['pass_flag']);
    }

    private function decode($response): array
    {
        $raw = $response->getBody();
        if (is_string($raw) && strpos($raw, '<!DOCTYPE html') !== false && preg_match('/<p>(.*?)<\\/p>/s', $raw, $m)) {
            $raw = html_entity_decode($m[1]);
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }
}
