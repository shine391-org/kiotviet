<?php

namespace Tests\Services;

use App\Services\Appointments\AppointmentService;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;

/**
 * @agent-test: AppointmentService MySQL testing
 * @agent-pattern: Service test with DevDatabaseTrait
 */
class AppointmentServiceTest extends CIUnitTestCase
{
    use DevDatabaseTrait;

    private AppointmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->service = new AppointmentService();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_schedules_appointment()
    {
        $start = '2025-01-01 09:00:00';
        $end = '2025-01-01 10:00:00';
        $result = $this->service->schedule([
            'customer_id' => 1,
            'contract_id' => null,
            'start_time' => $start,
            'end_time' => $end,
            'notes' => 'Initial call',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals($start, $result['data']['start_time']);
    }

    /** @test */
    public function it_prevents_conflicts()
    {
        $this->service->schedule([
            'start_time' => '2025-02-01 09:00:00',
            'end_time' => '2025-02-01 10:00:00',
            'notes' => 'Baseline',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->schedule([
            'start_time' => '2025-02-01 09:30:00',
            'end_time' => '2025-02-01 10:30:00',
            'notes' => 'Overlaps',
        ]);
    }

    /** @test */
    public function it_reschedules_and_cancels()
    {
        $appt = $this->service->schedule([
            'start_time' => '2025-03-01 13:00:00',
            'end_time' => '2025-03-01 14:00:00',
        ])['data'];

        $updated = $this->service->reschedule($appt['id'], [
            'start_time' => '2025-03-01 15:00:00',
            'end_time' => '2025-03-01 16:00:00',
            'status' => 'scheduled',
        ]);
        $this->assertEquals('2025-03-01 15:00:00', $updated['data']['start_time']);

        $cancelled = $this->service->cancel($appt['id']);
        $this->assertEquals('cancelled', $cancelled['data']['status']);
    }
}
