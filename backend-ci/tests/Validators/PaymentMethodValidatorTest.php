<?php

namespace Tests\Validators;

use App\Validators\PaymentMethodValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

class PaymentMethodValidatorTest extends CIUnitTestCase
{
    private PaymentMethodValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new PaymentMethodValidator();
    }

    public function testValidateListFiltersDefaults(): void
    {
        $result = $this->validator->validateListFilters([]);
        
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(20, $result['limit']);
        $this->assertTrue($result['is_active']);
    }

    public function testValidateListFiltersWithValues(): void
    {
        $result = $this->validator->validateListFilters([
            'page' => 2,
            'limit' => 50,
            'search' => 'test',
            'is_active' => 'false',
        ]);
        
        $this->assertEquals(2, $result['page']);
        $this->assertEquals(50, $result['limit']);
        $this->assertEquals('test', $result['search']);
        $this->assertFalse($result['is_active']);
    }

    public function testValidateListFiltersTrimSearch(): void
    {
        $result = $this->validator->validateListFilters(['search' => '  test  ']);
        
        $this->assertEquals('test', $result['search']);
    }

    public function testValidateCreateSuccess(): void
    {
        $data = ['code' => 'CASH', 'name' => 'Cash Payment'];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('CASH', $result['code']);
        $this->assertEquals('Cash Payment', $result['name']);
        $this->assertTrue($result['is_active']);
        $this->assertEquals(0, $result['display_order']);
    }

    public function testValidateCreateUppercasesCode(): void
    {
        $data = ['code' => 'cash', 'name' => 'Cash'];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('CASH', $result['code']);
    }

    public function testValidateCreateTrimsCode(): void
    {
        $data = ['code' => '  CASH  ', 'name' => 'Cash'];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('CASH', $result['code']);
    }

    public function testValidateCreateThrowsWhenCodeEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment method code is required');
        
        $this->validator->validateCreate(['code' => '', 'name' => 'Test']);
    }

    public function testValidateCreateThrowsWhenCodeInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment method code must be UPPERCASE alphanumeric with underscores');
        
        $this->validator->validateCreate(['code' => 'cash-123', 'name' => 'Test']);
    }

    public function testValidateCreateThrowsWhenNameEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment method name is required');
        
        $this->validator->validateCreate(['code' => 'CASH', 'name' => '']);
    }

    public function testValidateCreateThrowsWhenNameTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Payment method name is too long');
        
        $this->validator->validateCreate(['code' => 'CASH', 'name' => str_repeat('A', 300)]);
    }

    public function testValidateCreateThrowsWhenDisplayOrderNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Display order must be >= 0');
        
        $this->validator->validateCreate(['code' => 'CASH', 'name' => 'Cash', 'display_order' => -1]);
    }

    public function testValidateCreateWithDescription(): void
    {
        $data = ['code' => 'CASH', 'name' => 'Cash', 'description' => 'Cash payment method'];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('Cash payment method', $result['description']);
    }

    public function testValidateCreateWithTranslations(): void
    {
        $data = [
            'code' => 'CASH',
            'name' => 'Cash',
            'name_translations' => ['vi' => 'Tiền mặt', 'en' => 'Cash'],
        ];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('Tiền mặt', $result['name_translations']['vi']);
        $this->assertEquals('Cash', $result['name_translations']['en']);
    }

    public function testValidateCreateWithInvalidTranslations(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('name_translations must be an object');
        
        $this->validator->validateCreate([
            'code' => 'CASH',
            'name' => 'Cash',
            'name_translations' => 'invalid',
        ]);
    }

    public function testValidateUpdateSuccess(): void
    {
        $data = ['name' => 'Updated Name'];
        $result = $this->validator->validateUpdate($data);
        
        $this->assertEquals('Updated Name', $result['name']);
        $this->assertArrayNotHasKey('code', $result);
    }

    public function testValidateUpdateWithCode(): void
    {
        $data = ['code' => 'new_code'];
        $result = $this->validator->validateUpdate($data);
        
        $this->assertEquals('NEW_CODE', $result['code']);
    }

    public function testValidateUpdateThrowsWhenCodeEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateUpdate(['code' => '']);
    }

    public function testValidateUpdateThrowsWhenCodeInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateUpdate(['code' => 'invalid-code']);
    }

    public function testValidateUpdateThrowsWhenNameEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateUpdate(['name' => '']);
    }

    public function testValidateUpdateThrowsWhenNameTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateUpdate(['name' => str_repeat('A', 300)]);
    }

    public function testValidateUpdateThrowsWhenNoFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No fields to update');
        
        $this->validator->validateUpdate([]);
    }

    public function testValidateUpdateWithDescription(): void
    {
        $data = ['description' => '  New description  '];
        $result = $this->validator->validateUpdate($data);
        
        $this->assertEquals('New description', $result['description']);
    }

    public function testValidateUpdateWithNullDescription(): void
    {
        $data = ['description' => null];
        $result = $this->validator->validateUpdate($data);
        
        $this->assertNull($result['description']);
    }

    public function testValidateUpdateWithIsActive(): void
    {
        $data = ['is_active' => 'false'];
        $result = $this->validator->validateUpdate($data);
        
        $this->assertFalse($result['is_active']);
    }

    public function testValidateUpdateWithDisplayOrder(): void
    {
        $data = ['display_order' => 5];
        $result = $this->validator->validateUpdate($data);
        
        $this->assertEquals(5, $result['display_order']);
    }

    public function testValidateUpdateThrowsWhenDisplayOrderNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->validator->validateUpdate(['display_order' => -1]);
    }

    public function testValidateUpdateWithTranslations(): void
    {
        $data = ['name_translations' => ['vi' => 'Tiền mặt']];
        $result = $this->validator->validateUpdate($data);
        
        $this->assertEquals('Tiền mặt', $result['name_translations']['vi']);
    }

    public function testValidateUpdateWithEmptyTranslations(): void
    {
        $data = ['name_translations' => []];
        $result = $this->validator->validateUpdate($data);
        
        $this->assertNull($result['name_translations']);
    }

    public function testValidateCreateWithCodeContainingUnderscore(): void
    {
        $data = ['code' => 'BANK_TRANSFER', 'name' => 'Bank Transfer'];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('BANK_TRANSFER', $result['code']);
    }

    public function testValidateCreateTrimsName(): void
    {
        $data = ['code' => 'CASH', 'name' => '  Cash Payment  '];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('Cash Payment', $result['name']);
    }

    public function testValidateCreateWithIsActiveFalse(): void
    {
        $data = ['code' => 'CASH', 'name' => 'Cash', 'is_active' => false];
        $result = $this->validator->validateCreate($data);
        
        $this->assertFalse($result['is_active']);
    }

    public function testTranslationTooLongThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Translation value is too long');
        
        $this->validator->validateCreate([
            'code' => 'CASH',
            'name' => 'Cash',
            'name_translations' => ['vi' => str_repeat('A', 300)],
        ]);
    }
}
