<?php

namespace Tests\Services;

use App\Services\Appointments\AppointmentService;
use App\Repositories\Appointments\AppointmentRepository;
use App\Validators\AppointmentValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use RuntimeException;

/**
 * @agent-test: AppointmentService (stubbed)
 * @agent-pattern: Service orchestration without DB
 */
class AppointmentServiceTest extends CIUnitTestCase
{
    private AppointmentService $service;
    private AppointmentServiceFakeRepo $repo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repo = new AppointmentServiceFakeRepo();
        $validator = new AppointmentValidator();
        $this->service = new AppointmentService($this->repo, $validator);
    }

    public function testScheduleCreatesAppointment(): void
    {
        $data = [
            'customer_id' => 1,
            'start_time' => '2024-06-15 10:00:00',
            'end_time' => '2024-06-15 11:00:00',
            'notes' => 'Test appointment',
        ];

        $result = $this->service->schedule($data);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['data']['customer_id']);
        $this->assertSame('scheduled', $result['data']['status']);
    }

    public function testScheduleRequiresStartTime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('start_time and end_time are required');

        $this->service->schedule([
            'customer_id' => 1,
            'end_time' => '2024-06-15 11:00:00',
        ]);
    }

    public function testScheduleRequiresEndTime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('start_time and end_time are required');

        $this->service->schedule([
            'customer_id' => 1,
            'start_time' => '2024-06-15 10:00:00',
        ]);
    }

    public function testScheduleValidatesEndTimeAfterStartTime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('end_time must be after start_time');

        $this->service->schedule([
            'customer_id' => 1,
            'start_time' => '2024-06-15 11:00:00',
            'end_time' => '2024-06-15 10:00:00',
        ]);
    }

    public function testScheduleDetectsConflict(): void
    {
        $this->repo->setHasConflict(true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Appointment conflict');

        $this->service->schedule([
            'customer_id' => 1,
            'start_time' => '2024-06-15 10:00:00',
            'end_time' => '2024-06-15 11:00:00',
        ]);
    }

    public function testRescheduleUpdatesAppointment(): void
    {
        $result = $this->service->reschedule(1, [
            'start_time' => '2024-06-15 14:00:00',
            'end_time' => '2024-06-15 15:00:00',
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
    }

    public function testRescheduleThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Appointment not found');

        $this->service->reschedule(999, [
            'start_time' => '2024-06-15 14:00:00',
            'end_time' => '2024-06-15 15:00:00',
        ]);
    }

    public function testRescheduleDetectsConflict(): void
    {
        $this->repo->setHasConflict(true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Appointment conflict');

        $this->service->reschedule(1, [
            'start_time' => '2024-06-15 14:00:00',
            'end_time' => '2024-06-15 15:00:00',
        ]);
    }

    public function testCancelUpdatesStatus(): void
    {
        $result = $this->service->cancel(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame('cancelled', $result['data']['status']);
    }

    public function testCancelThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Appointment not found');

        $this->service->cancel(999);
    }

    public function testGetReturnsAppointment(): void
    {
        $result = $this->service->get(1);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('data', $result);
        $this->assertSame(1, $result['data']['id']);
    }

    public function testGetThrowsWhenNotFound(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Appointment not found');

        $this->service->get(999);
    }
}

class AppointmentServiceFakeRepo extends AppointmentRepository
{
    private array $appointments = [];
    private bool $hasConflict = false;

    public function __construct()
    {
        $this->appointments = [
            1 => [
                'id' => 1,
                'customer_id' => 1,
                'start_time' => '2024-06-15 10:00:00',
                'end_time' => '2024-06-15 11:00:00',
                'status' => 'scheduled',
                'notes' => 'Existing appointment',
                'created_at' => '2024-06-01 00:00:00',
            ],
        ];
    }

    public function setHasConflict(bool $value): void
    {
        $this->hasConflict = $value;
    }

    public function findById(int $id): ?array
    {
        return $this->appointments[$id] ?? null;
    }

    public function hasConflict(\DateTimeInterface $start, \DateTimeInterface $end, ?int $ignoreId = null): bool
    {
        return $this->hasConflict;
    }

    public function create(array $data): array
    {
        $id = count($this->appointments) > 0 ? max(array_keys($this->appointments)) + 1 : 1;
        $data['id'] = $id;
        $data['created_at'] = date('Y-m-d H:i:s');
        $this->appointments[$id] = $data;
        return $data;
    }

    public function updateSchedule(int $id, array $data): bool
    {
        if (! isset($this->appointments[$id])) {
            return false;
        }
        $this->appointments[$id] = array_merge($this->appointments[$id], $data);
        return true;
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (! isset($this->appointments[$id])) {
            return false;
        }
        $this->appointments[$id]['status'] = $status;
        return true;
    }
}
