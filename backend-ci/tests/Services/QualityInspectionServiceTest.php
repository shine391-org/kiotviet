<?php

namespace Tests\Services;

use App\Repositories\Quality\QualityInspectionRepository;
use App\Repositories\Quality\QualityParameterRepository;
use App\Services\Quality\QualityInspectionService;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use RuntimeException;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\QualitySchemaTrait;

/**
 * @agent-test: QualityInspectionService
 * @agent-pattern: Service test with DevDatabaseTrait + schema reset
 */
class QualityInspectionServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use QualitySchemaTrait;

    private QualityInspectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetQualitySchema();
        $db = $this->db;
        $this->service = new QualityInspectionService(
            new QualityInspectionRepository(null, null, $db),
            new QualityParameterRepository(null, $db)
        );
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_submit_marks_fail_when_value_outside_threshold(): void
    {
        $parameterId = $this->seedParameter(['name' => 'Moisture', 'min_value' => 10, 'max_value' => 12]);

        $inspection = $this->service->createInspection([
            'reference_type' => 'receiving',
            'reference_id' => 99,
            'inspected_by' => 2,
            'items' => [
                ['parameter_id' => $parameterId, 'value_numeric' => 8.5, 'notes' => 'Too low'],
            ],
        ]);

        $submitted = $this->service->submitInspection($inspection['data']['id']);

        $this->assertSame('submitted', $submitted['data']['status']);
        $this->assertSame('fail', $submitted['data']['result']);
        $item = $this->db->table('quality_inspection_items')->where('inspection_id', $inspection['data']['id'])->get()->getRowArray();
        $this->assertSame('0', (string) $item['pass_flag']);
    }

    public function test_can_submit_and_approve_passed_inspection(): void
    {
        $parameterId = $this->seedParameter(['name' => 'Weight', 'min_value' => 4.5, 'max_value' => 6.0]);

        $inspection = $this->service->createInspection([
            'reference_type' => 'delivery_note',
            'reference_id' => 501,
            'inspected_by' => 11,
            'items' => [
                ['parameter_id' => $parameterId, 'value_numeric' => 5.0],
            ],
        ]);

        $submitted = $this->service->submitInspection($inspection['data']['id']);
        $this->assertSame('pass', $submitted['data']['result']);
        $this->assertSame('submitted', $submitted['data']['status']);

        $approved = $this->service->approveInspection($inspection['data']['id'], ['approved_by' => 19]);
        $this->assertSame('approved', $approved['data']['status']);
        $this->assertEquals(19, (int) $approved['data']['approved_by']);
    }

    public function test_create_inspection_throws_when_parameter_missing(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->createInspection([
            'reference_type' => 'delivery_note',
            'reference_id' => 222,
            'items' => [
                ['parameter_id' => 9999, 'value_numeric' => 1.2],
            ],
        ]);
    }

    public function test_approve_requires_submitted_status(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $parameterId = $this->seedParameter(['name' => 'Visual', 'specification' => 'OK']);
        $inspection = $this->service->createInspection([
            'reference_type' => 'receiving',
            'reference_id' => 300,
            'items' => [
                ['parameter_id' => $parameterId, 'value_text' => 'OK'],
            ],
        ]);

        $this->service->approveInspection($inspection['data']['id'], ['approved_by' => 1]);
    }

    private function seedParameter(array $data): int
    {
        $payload = array_merge([
            'name' => 'Default Parameter',
            'uom' => 'unit',
            'min_value' => null,
            'max_value' => null,
            'specification' => null,
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ], $data);

        $this->db->table('quality_parameters')->insert($payload);
        return (int) $this->db->insertID();
    }
}
