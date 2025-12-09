<?php

namespace Tests\Validators;

use App\Validators\CustomerValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: CustomerValidator
 * @agent-pattern: Validator unit coverage
 */
class CustomerValidatorTest extends CIUnitTestCase
{
    private CustomerValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new CustomerValidator();
    }

    public function testValidateListFiltersCastsValues(): void
    {
        $result = $this->validator->validateListFilters([
            'page' => '2',
            'limit' => '50',
            'search' => ' John ',
        ]);

        $this->assertSame(2, $result['page']);
        $this->assertSame(50, $result['limit']);
        $this->assertSame('John', $result['search']);
    }

    public function testValidateListFiltersDefaultValues(): void
    {
        $result = $this->validator->validateListFilters([]);

        $this->assertSame(1, $result['page']);
        $this->assertSame(20, $result['limit']);
    }

    public function testValidateCreateRequiresName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate(['phone' => '0123456789']);
    }

    public function testValidateCreateAcceptsValidData(): void
    {
        $data = [
            'name' => 'John Doe',
            'phone' => '0123456789',
            'email' => 'john@example.com',
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame('John Doe', $result['name']);
        $this->assertSame('0123456789', $result['phone']);
        $this->assertSame('john@example.com', $result['email']);
    }

    public function testValidateCreateTrimsWhitespace(): void
    {
        $data = [
            'name' => '  John Doe  ',
            'phone' => ' 0123456789 ',
        ];

        $result = $this->validator->validateCreate($data);

        $this->assertSame('John Doe', $result['name']);
    }

    public function testValidateCreateThrowsOnInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'name' => 'John',
            'email' => 'invalid-email',
        ]);
    }

    public function testValidateUpdateAcceptsValidData(): void
    {
        $data = ['name' => 'Jane Doe'];

        $result = $this->validator->validateUpdate($data);

        $this->assertSame('Jane Doe', $result['name']);
    }

    public function testValidateListFiltersWithStatus(): void
    {
        $result = $this->validator->validateListFilters(['status' => 'active']);

        $this->assertArrayHasKey('status', $result);
    }

    public function testValidateListFiltersWithCustomerType(): void
    {
        $result = $this->validator->validateListFilters(['customer_type' => 'individual']);

        $this->assertArrayHasKey('customer_type', $result);
    }

    public function testValidateListFiltersWithGender(): void
    {
        $result = $this->validator->validateListFilters(['gender' => 'male']);

        $this->assertArrayHasKey('gender', $result);
    }

    public function testValidateListFiltersWithDateRange(): void
    {
        $result = $this->validator->validateListFilters([
            'created_from' => '2024-01-01',
            'created_to' => '2024-12-31',
        ]);

        $this->assertArrayHasKey('created_from', $result);
        $this->assertArrayHasKey('created_to', $result);
    }
}
