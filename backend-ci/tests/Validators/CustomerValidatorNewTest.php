<?php

namespace Tests\Validators;

use App\Validators\CustomerValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

class CustomerValidatorNewTest extends CIUnitTestCase
{
    private CustomerValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new CustomerValidator();
    }

    public function testValidateListFiltersDefaults(): void
    {
        $result = $this->validator->validateListFilters([]);
        
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(20, $result['limit']);
    }

    public function testValidateListFiltersWithValues(): void
    {
        $result = $this->validator->validateListFilters([
            'page' => 2,
            'limit' => 50,
            'search' => 'test',
            'customer_type' => 'company',
            'gender' => 'male',
            'status' => 'active',
        ]);
        
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(50, $result['limit']);
        $this->assertEquals('test', $result['search']);
        $this->assertEquals('COMPANY', $result['customer_type']);
        $this->assertEquals('MALE', $result['gender']);
        $this->assertEquals('ACTIVE', $result['status']);
    }

    public function testValidateListFiltersTrimsSearch(): void
    {
        $result = $this->validator->validateListFilters(['search' => '  test  ']);
        
        $this->assertEquals('test', $result['search']);
    }

    public function testValidateCreateSuccess(): void
    {
        $data = ['name' => 'John Doe'];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('INDIVIDUAL', $result['customer_type']);
    }

    public function testValidateCreateWithAllFields(): void
    {
        $data = [
            'name' => 'John',
            'customer_type' => 'company',
            'email' => 'john@example.com',
            'phone' => '0901234567',
            'address' => '123 Main St',
            'gender' => 'male',
        ];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('john@example.com', $result['email']);
        $this->assertEquals('0901234567', $result['phone']);
        $this->assertEquals('COMPANY', $result['customer_type']);
        $this->assertEquals('MALE', $result['gender']);
    }

    public function testValidateCreateThrowsOnEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer name is required');
        
        $this->validator->validateCreate(['name' => '']);
    }

    public function testValidateCreateThrowsOnInvalidCustomerType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('customer_type must be one of');
        
        $this->validator->validateCreate(['name' => 'John', 'customer_type' => 'invalid']);
    }

    public function testValidateCreateThrowsOnInvalidGender(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('gender must be one of');
        
        $this->validator->validateCreate(['name' => 'John', 'gender' => 'invalid']);
    }

    public function testValidateCreateThrowsOnInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('email is not a valid email');
        
        $this->validator->validateCreate(['name' => 'John', 'email' => 'invalid']);
    }

    public function testValidateCreateThrowsOnInvalidPhone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('phone must be 6-20 digits');
        
        $this->validator->validateCreate(['name' => 'John', 'phone' => '123']);
    }

    public function testValidateCreateThrowsOnInvalidTaxCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('tax_code must be 10-14 digits');
        
        $this->validator->validateCreate(['name' => 'John', 'tax_code' => '123']);
    }

    public function testValidateCreateThrowsOnInvalidUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('facebook must be a valid URL');
        
        $this->validator->validateCreate(['name' => 'John', 'facebook' => 'not-a-url']);
    }

    public function testValidateUpdateSuccess(): void
    {
        $result = $this->validator->validateUpdate(['name' => 'Updated']);
        
        $this->assertEquals('Updated', $result['name']);
    }

    public function testValidateUpdateThrowsWhenNoFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No fields to update');
        
        $this->validator->validateUpdate([]);
    }

    public function testValidateUpdateWithPartialFields(): void
    {
        $result = $this->validator->validateUpdate([
            'email' => 'new@example.com',
            'phone' => '0987654321',
        ]);
        
        $this->assertEquals('new@example.com', $result['email']);
        $this->assertEquals('0987654321', $result['phone']);
        $this->assertArrayNotHasKey('name', $result);
    }

    public function testValidateCreateWithBirthday(): void
    {
        $data = ['name' => 'John', 'birthday' => '1990-05-15'];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('1990-05-15', $result['birthday']);
    }

    public function testValidateCreateThrowsOnInvalidBirthday(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date for birthday');
        
        $this->validator->validateCreate(['name' => 'John', 'birthday' => 'not-a-date']);
    }

    public function testValidateCreateWithStatus(): void
    {
        $data = ['name' => 'John', 'status' => 'active'];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('ACTIVE', $result['status']);
    }

    public function testValidateCreateThrowsOnInvalidStatus(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid status');
        
        $this->validator->validateCreate(['name' => 'John', 'status' => 'invalid']);
    }

    public function testValidateCreateWithCustomerGroupId(): void
    {
        $data = ['name' => 'John', 'customer_group_id' => 5];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals(5, $result['customer_group_id']);
    }

    public function testValidateCreateWithNullCustomerGroupId(): void
    {
        $data = ['name' => 'John', 'customer_group_id' => null];
        $result = $this->validator->validateCreate($data);
        
        $this->assertNull($result['customer_group_id']);
    }

    public function testValidateCreateWithMoney(): void
    {
        $data = ['name' => 'John', 'current_debt' => 100000, 'total_sales' => 500000];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals(100000, $result['current_debt']);
        $this->assertEquals(500000, $result['total_sales']);
    }

    public function testValidateUpdateWithNullValues(): void
    {
        $result = $this->validator->validateUpdate([
            'customer_group_id' => null,
            'created_by' => null,
        ]);
        
        $this->assertNull($result['customer_group_id']);
        $this->assertNull($result['created_by']);
    }

    public function testValidateCreateWithInvoiceFields(): void
    {
        $data = [
            'name' => 'John',
            'invoice_company_name' => 'ACME Corp',
            'invoice_address' => '456 Business St',
            'invoice_email' => 'invoice@acme.com',
        ];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('ACME Corp', $result['invoice_company_name']);
        $this->assertEquals('456 Business St', $result['invoice_address']);
        $this->assertEquals('invoice@acme.com', $result['invoice_email']);
    }

    public function testValidateCreateWithValidTaxCode(): void
    {
        $data = ['name' => 'John', 'tax_code' => '0123456789'];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('0123456789', $result['tax_code']);
    }

    public function testValidateListFiltersWithDateRange(): void
    {
        $result = $this->validator->validateListFilters([
            'created_from' => '2024-01-01',
            'created_to' => '2024-12-31',
        ]);
        
        $this->assertEquals('2024-01-01', $result['created_from']);
        $this->assertEquals('2024-12-31', $result['created_to']);
    }

    public function testValidateListFiltersWithDebtRange(): void
    {
        $result = $this->validator->validateListFilters([
            'debt_from' => 0,
            'debt_to' => 1000000,
        ]);
        
        $this->assertEquals(0, $result['debt_from']);
        $this->assertEquals(1000000, $result['debt_to']);
    }
}
