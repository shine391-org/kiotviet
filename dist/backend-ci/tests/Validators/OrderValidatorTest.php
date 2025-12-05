<?php

namespace Tests\Validators;

use App\Validators\OrderValidator;
use CodeIgniter\Test\CIUnitTestCase;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\CompleteSchemaTrait;

/**
 * @agent-test: OrderValidator @agent-pattern: Validator test with DevDatabaseTrait
 */
class OrderValidatorTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use CompleteSchemaTrait;

    private OrderValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetCompleteSchema();
        $this->validator = new OrderValidator();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    /** @test */
    public function it_rejects_fractional_quantities_when_serials_provided()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('serial_numbers count must match quantity');

        $this->validator->validateOrder([
            'items' => [
                ['product_id' => 1, 'quantity' => '2.5', 'serial_numbers' => ['SN1', 'SN2']],
            ],
        ]);
    }

    /** @test */
    public function it_accepts_integer_quantity_matching_serial_count()
    {
        $result = $this->validator->validateOrder([
            'items' => [
                ['product_id' => 1, 'quantity' => '2', 'serial_numbers' => ['SN1', 'SN2']],
            ],
        ]);

        $this->assertSame(2.0, $result['items'][0]['quantity']);
        $this->assertSame(['SN1', 'SN2'], $result['items'][0]['serial_numbers']);
    }
}
