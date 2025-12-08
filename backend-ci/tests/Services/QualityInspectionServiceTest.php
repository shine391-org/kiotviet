<?php

namespace Tests\Services;

use App\Services\Quality\QualityInspectionService;
use App\Repositories\Quality\QualityInspectionRepository;
use App\Repositories\Quality\QualityParameterRepository;
use App\Validators\QualityInspectionValidator;
use App\Validators\QualityParameterValidator;
use CodeIgniter\Test\CIUnitTestCase;
use RuntimeException;
use InvalidArgumentException;

/**
 * @agent-test: QualityInspectionService (stubbed)
 * @agent-pattern: Service orchestration without DB
 */
class QualityInspectionServiceTest extends CIUnitTestCase
{
    private QualityInspectionService $service;
    private QualityInspectionServiceFakeInspectionRepo $inspectionRepo;
    private QualityInspectionServiceFakeParameterRepo $parameterRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->inspectionRepo = new QualityInspectionServiceFakeInspectionRepo();
        $this->parameterRepo = new QualityInspectionServiceFakeParameterRepo();

        $inspectionValidator = new QualityInspectionValidator();
        $parameterValidator = new QualityParameterValidator();

        $this->service = new QualityInspectionService(
            $this->inspectionRepo,
            $this->parameterRepo,
            $inspectionValidator,
            $parameterValidator
        );
    }

    // Parameter Tests

    public function testListParametersReturnsData(): void
    {
        $result = $this->service->listParameters([]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
        $this->assertCount(2, $result['data']);
    }

    public function testCreateParameterReturnsNewParameter(): void
    {
        $data = [
            'name' => 'Temperature Test',
            'uom' => '°C',
            'min_value' => 15,
            'max_value' => 30,
        ];

        $result = $this->service->createParameter($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('Temperature Test', $result['data']['name']);
    }

    public function testCreateParameterThrowsOnDuplicateName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quality parameter name already exists');

        $this->service->createParameter([
            'name' => 'Weight Check', // Already exists in fake data
        ]);
    }

    public function testUpdateParameterReturnsUpdatedParameter(): void
    {
        $result = $this->service->updateParameter(1, ['name' => 'Updated Weight Check']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testUpdateParameterThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Quality parameter not found');

        $this->service->updateParameter(999, ['name' => 'Test']);
    }

    public function testUpdateParameterThrowsOnDuplicateName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Quality parameter name already exists');

        $this->service->updateParameter(1, [
            'name' => 'Dimension Check', // ID 2 already has this name
        ]);
    }

    public function testDeleteParameterReturnsSuccess(): void
    {
        $result = $this->service->deleteParameter(1);

        $this->assertTrue($result['success']);
    }

    public function testDeleteParameterThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Quality parameter not found');

        $this->service->deleteParameter(999);
    }

    public function testDeleteParameterThrowsWhenUsed(): void
    {
        $this->parameterRepo->usedIds[] = 1;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter is used by inspections');

        $this->service->deleteParameter(1);
    }

    // Inspection Tests

    public function testListInspectionsReturnsData(): void
    {
        $result = $this->service->listInspections([]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertIsArray($result['data']);
    }

    public function testShowReturnsInspection(): void
    {
        $result = $this->service->show(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['data']['id']);
        $this->assertSame('draft', $result['data']['status']);
    }

    public function testShowThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Inspection not found');

        $this->service->show(999);
    }

    public function testCreateInspectionReturnsDraftInspection(): void
    {
        $data = [
            'reference_type' => 'purchase_order',
            'reference_id' => 100,
            'inspected_by' => 1,
            'items' => [
                [
                    'parameter_id' => 1,
                    'value_numeric' => 50.5,
                ],
            ],
        ];

        $result = $this->service->createInspection($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('draft', $result['data']['status']);
        $this->assertSame('pending', $result['data']['result']);
    }

    public function testCreateInspectionValidatesRequiredFields(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->service->createInspection([
            // Missing required fields
        ]);
    }

    public function testCreateInspectionThrowsOnInvalidParameter(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Quality parameter not found');

        $this->service->createInspection([
            'reference_type' => 'purchase_order',
            'reference_id' => 100,
            'items' => [
                [
                    'parameter_id' => 999, // Non-existent
                    'value_numeric' => 50,
                ],
            ],
        ]);
    }

    public function testSubmitInspectionTransitionsToSubmitted(): void
    {
        $result = $this->service->submitInspection(1, ['inspected_by' => 1]);

        $this->assertTrue($result['success']);
        $this->assertSame('submitted', $result['data']['status']);
        $this->assertContains($result['data']['result'], ['pass', 'fail']);
    }

    public function testSubmitInspectionThrowsWhenNotDraft(): void
    {
        $this->inspectionRepo->fakeInspections[1]['status'] = 'submitted';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only draft inspections can be submitted');

        $this->service->submitInspection(1);
    }

    public function testApproveInspectionTransitionsToApproved(): void
    {
        $this->inspectionRepo->fakeInspections[1]['status'] = 'submitted';
        $this->inspectionRepo->fakeInspections[1]['result'] = 'pass';

        $result = $this->service->approveInspection(1, ['approved_by' => 1]);

        $this->assertTrue($result['success']);
        $this->assertSame('approved', $result['data']['status']);
    }

    public function testApproveInspectionThrowsWhenNotSubmitted(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Inspection must be submitted before approval');

        $this->service->approveInspection(1, ['approved_by' => 1]);
    }

    public function testApproveInspectionThrowsWhenFailed(): void
    {
        $this->inspectionRepo->fakeInspections[1]['status'] = 'submitted';
        $this->inspectionRepo->fakeInspections[1]['result'] = 'fail';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot approve a failed inspection');

        $this->service->approveInspection(1, ['approved_by' => 1]);
    }

    public function testRejectInspectionTransitionsToRejected(): void
    {
        $result = $this->service->rejectInspection(1, [
            'rejected_by' => 1,
            'reason' => 'Does not meet standards',
        ]);

        $this->assertTrue($result['success']);
        $this->assertSame('rejected', $result['data']['status']);
        $this->assertSame('fail', $result['data']['result']);
    }

    public function testRejectInspectionThrowsWhenAlreadyApproved(): void
    {
        $this->inspectionRepo->fakeInspections[1]['status'] = 'approved';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Only draft or submitted inspections can be rejected');

        $this->service->rejectInspection(1, ['rejected_by' => 1]);
    }
}

// Fake repository classes for testing

class QualityInspectionServiceFakeInspectionRepo extends QualityInspectionRepository
{
    public array $fakeInspections = [];
    private int $nextId = 3;

    public function __construct()
    {
        $this->fakeInspections = [
            1 => [
                'id' => 1,
                'reference_type' => 'purchase_order',
                'reference_id' => 1,
                'status' => 'draft',
                'result' => 'pending',
                'inspected_by' => null,
                'inspected_at' => null,
                'submitted_at' => null,
                'approved_by' => null,
                'approved_at' => null,
                'notes' => 'Test inspection',
                'items' => [
                    [
                        'id' => 1,
                        'inspection_id' => 1,
                        'parameter_id' => 1,
                        'parameter_name' => 'Weight Check',
                        'value_numeric' => 50.5,
                        'value_text' => '50.5',
                        'pass_flag' => null,
                    ]
                ],
            ],
            2 => [
                'id' => 2,
                'reference_type' => 'sales_order',
                'reference_id' => 2,
                'status' => 'submitted',
                'result' => 'pass',
                'inspected_by' => 1,
                'inspected_at' => '2024-01-01 10:00:00',
                'submitted_at' => '2024-01-01 11:00:00',
                'approved_by' => null,
                'approved_at' => null,
                'notes' => null,
                'items' => [],
            ],
        ];
    }

    public function db(): \CodeIgniter\Database\BaseConnection
    {
        return \Config\Database::connect('tests');
    }

    public function list(array $filters = []): array
    {
        return array_values($this->fakeInspections);
    }

    public function find(int $id): ?array
    {
        $inspection = $this->fakeInspections[$id] ?? null;
        if ($inspection) {
            unset($inspection['items']);
        }
        return $inspection;
    }

    public function findWithItems(int $id): ?array
    {
        return $this->fakeInspections[$id] ?? null;
    }

    public function create(array $inspection, array $items): array
    {
        $id = $this->nextId++;
        $inspection['id'] = $id;
        $inspection['created_at'] = date('Y-m-d H:i:s');
        $inspection['updated_at'] = date('Y-m-d H:i:s');

        $inspectionItems = [];
        foreach ($items as $idx => $item) {
            $inspectionItems[] = $item + [
                'id' => $id * 100 + $idx,
                'inspection_id' => $id,
            ];
        }
        $inspection['items'] = $inspectionItems;
        $this->fakeInspections[$id] = $inspection;

        return $inspection;
    }

    public function update(int $id, array $data): array
    {
        if (isset($this->fakeInspections[$id])) {
            $this->fakeInspections[$id] = array_merge($this->fakeInspections[$id], $data);
        }
        return $this->fakeInspections[$id] ?? [];
    }

    public function updateEvaluation(int $id, array $items, array $meta): array
    {
        if (!isset($this->fakeInspections[$id])) {
            return [];
        }

        // Update meta
        $this->fakeInspections[$id] = array_merge($this->fakeInspections[$id], $meta);

        // Update items
        if ($items && isset($this->fakeInspections[$id]['items'])) {
            foreach ($items as $itemUpdate) {
                foreach ($this->fakeInspections[$id]['items'] as &$item) {
                    if ($item['id'] === ($itemUpdate['id'] ?? 0)) {
                        $item = array_merge($item, $itemUpdate);
                        break;
                    }
                }
            }
        }

        return $this->fakeInspections[$id];
    }

    public function parameterIds(int $inspectionId): array
    {
        $inspection = $this->fakeInspections[$inspectionId] ?? null;
        if (!$inspection || !isset($inspection['items'])) {
            return [];
        }
        return array_column($inspection['items'], 'parameter_id');
    }

    public function itemsByInspection(int $inspectionId): array
    {
        $inspection = $this->fakeInspections[$inspectionId] ?? null;
        return $inspection['items'] ?? [];
    }
}

class QualityInspectionServiceFakeParameterRepo extends QualityParameterRepository
{
    public array $fakeParameters = [];
    public array $usedIds = [];
    private int $nextId = 3;

    public function __construct()
    {
        $this->fakeParameters = [
            1 => [
                'id' => 1,
                'name' => 'Weight Check',
                'uom' => 'kg',
                'min_value' => 10,
                'max_value' => 100,
                'specification' => null,
                'is_active' => 1,
            ],
            2 => [
                'id' => 2,
                'name' => 'Dimension Check',
                'uom' => 'cm',
                'min_value' => 5,
                'max_value' => 50,
                'specification' => null,
                'is_active' => 1,
            ],
        ];
    }

    public function list(array $filters = []): array
    {
        return array_values($this->fakeParameters);
    }

    public function count(array $filters = []): int
    {
        return count($this->fakeParameters);
    }

    public function find(int $id): ?array
    {
        return $this->fakeParameters[$id] ?? null;
    }

    public function findByIds(array $ids): array
    {
        $result = [];
        foreach ($ids as $id) {
            if (isset($this->fakeParameters[$id])) {
                $result[$id] = $this->fakeParameters[$id];
            }
        }
        return $result;
    }

    public function create(array $data): array
    {
        $id = $this->nextId++;
        $data['id'] = $id;
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->fakeParameters[$id] = $data;
        return $data;
    }

    public function update(int $id, array $data): array
    {
        if (isset($this->fakeParameters[$id])) {
            $this->fakeParameters[$id] = array_merge($this->fakeParameters[$id], $data);
        }
        return $this->fakeParameters[$id] ?? [];
    }

    public function delete(int $id): void
    {
        unset($this->fakeParameters[$id]);
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        foreach ($this->fakeParameters as $param) {
            if ($param['name'] === $name && $param['id'] !== $excludeId) {
                return true;
            }
        }
        return false;
    }

    public function isUsed(int $parameterId): bool
    {
        return in_array($parameterId, $this->usedIds, true);
    }

    public function db(): \CodeIgniter\Database\BaseConnection
    {
        return \Config\Database::connect('tests');
    }
}
