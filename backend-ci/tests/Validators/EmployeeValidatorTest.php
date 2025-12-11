<?php

namespace Tests\Validators;

use App\Validators\EmployeeValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: EmployeeValidator
 * @agent-pattern: Validator unit tests
 */
class EmployeeValidatorTest extends CIUnitTestCase
{
    private EmployeeValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new EmployeeValidator();
    }

    public function testValidateCreateWithRequiredFieldsOnly(): void
    {
        $data = ['full_name' => 'Nguyen Van A'];
        $result = $this->validator->validateCreate($data);

        $this->assertEquals('Nguyen Van A', $result['full_name']);
        $this->assertEquals('active', $result['status']);
    }

    public function testValidateCreateWithAllFields(): void
    {
        $data = [
            'full_name' => 'Tran Thi B',
            'branch_id' => 5,
            'status' => 'inactive',
            'join_date' => '2024-03-01',
        ];
        $result = $this->validator->validateCreate($data);

        $this->assertEquals('Tran Thi B', $result['full_name']);
        $this->assertEquals(5, $result['branch_id']);
        $this->assertEquals('inactive', $result['status']);
        $this->assertEquals('2024-03-01', $result['join_date']);
    }

    public function testValidateCreateThrowsOnMissingFullName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([]);
    }

    public function testValidateCreateThrowsOnEmptyFullName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate(['full_name' => '']);
    }

    public function testValidateCreateThrowsOnTooLongFullName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate(['full_name' => str_repeat('A', 300)]);
    }

    public function testValidateCreateThrowsOnInvalidJoinDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'full_name' => 'Test Employee',
            'join_date' => 'invalid-date',
        ]);
    }

    public function testValidateCreateThrowsOnNegativeBranchId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'full_name' => 'Test',
            'branch_id' => -1,
        ]);
    }

    public function testValidateUpdateSuccess(): void
    {
        $data = [
            'full_name' => 'Updated Name',
            'status' => 'inactive',
        ];
        $result = $this->validator->validateUpdate($data);

        $this->assertEquals('Updated Name', $result['full_name']);
        $this->assertEquals('inactive', $result['status']);
    }

    public function testValidateUpdateWithPartialFields(): void
    {
        $data = ['status' => 'active'];
        $result = $this->validator->validateUpdate($data);

        $this->assertEquals('active', $result['status']);
        $this->assertArrayNotHasKey('full_name', $result);
    }

    public function testValidateUpdateThrowsOnInvalidBranchId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateUpdate(['branch_id' => -5]);
    }

    public function testValidateUpdateThrowsOnTooLongFullName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateUpdate(['full_name' => str_repeat('A', 300)]);
    }

    public function testValidateUpdateThrowsOnInvalidJoinDate(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateUpdate(['join_date' => 'not-a-date']);
    }

    public function testValidateCreateWithZeroBranchId(): void
    {
        $data = [
            'full_name' => 'Test Employee',
            'branch_id' => 0,
        ];
        $result = $this->validator->validateCreate($data);

        $this->assertEquals(0, $result['branch_id']);
    }

    public function testValidateUpdateWithAllFields(): void
    {
        $data = [
            'full_name' => 'New Name',
            'branch_id' => 10,
            'status' => 'on_leave',
            'join_date' => '2024-06-15',
        ];
        $result = $this->validator->validateUpdate($data);

        $this->assertEquals('New Name', $result['full_name']);
        $this->assertEquals(10, $result['branch_id']);
        $this->assertEquals('on_leave', $result['status']);
        $this->assertEquals('2024-06-15', $result['join_date']);
    }

    public function testValidateCreateWithStatusTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'full_name' => 'Test',
            'status' => str_repeat('A', 50),
        ]);
    }
}
