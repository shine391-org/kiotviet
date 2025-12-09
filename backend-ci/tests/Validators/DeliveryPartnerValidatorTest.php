<?php

namespace Tests\Validators;

use App\Validators\DeliveryPartnerValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

class DeliveryPartnerValidatorTest extends CIUnitTestCase
{
    private DeliveryPartnerValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new DeliveryPartnerValidator();
    }

    public function testValidateListFiltersDefaults(): void
    {
        $result = $this->validator->validateListFilters([]);
        
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(20, $result['limit']);
        $this->assertEquals('', $result['search']);
        $this->assertEquals('created_at', $result['sort_by']);
        $this->assertEquals('desc', $result['sort_order']);
    }

    public function testValidateListFiltersWithValues(): void
    {
        $result = $this->validator->validateListFilters([
            'page' => 3,
            'limit' => 50,
            'search' => '  test  ',
            'sort_by' => 'name',
            'sort_order' => 'asc',
        ]);
        
        $this->assertEquals(3, $result['page']);
        $this->assertEquals(50, $result['limit']);
        $this->assertEquals('test', $result['search']);
        $this->assertEquals('name', $result['sort_by']);
        $this->assertEquals('asc', $result['sort_order']);
    }

    public function testValidateListFiltersPageMinimum(): void
    {
        $result = $this->validator->validateListFilters(['page' => 0]);
        
        $this->assertEquals(1, $result['page']);
    }

    public function testValidateListFiltersLimitMax(): void
    {
        $result = $this->validator->validateListFilters(['limit' => 200]);
        
        $this->assertEquals(100, $result['limit']);
    }

    public function testValidateListFiltersLimitMin(): void
    {
        $result = $this->validator->validateListFilters(['limit' => 0]);
        
        $this->assertEquals(1, $result['limit']);
    }

    public function testValidateListFiltersInvalidSortBy(): void
    {
        $result = $this->validator->validateListFilters(['sort_by' => 'invalid']);
        
        $this->assertEquals('created_at', $result['sort_by']);
    }

    public function testValidateListFiltersValidSortBy(): void
    {
        $validSortBy = ['code', 'name', 'created_at', 'debt_amount'];
        foreach ($validSortBy as $sort) {
            $result = $this->validator->validateListFilters(['sort_by' => $sort]);
            $this->assertEquals($sort, $result['sort_by']);
        }
    }

    public function testValidateListFiltersInvalidSortOrder(): void
    {
        $result = $this->validator->validateListFilters(['sort_order' => 'invalid']);
        
        $this->assertEquals('desc', $result['sort_order']);
    }

    public function testValidateCreateSuccess(): void
    {
        $data = ['name' => 'Test Partner', 'phone' => '0901234567'];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('Test Partner', $result['name']);
        $this->assertNotEmpty($result['code']);
    }

    public function testValidateCreateThrowsWhenNameEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Partner name is required');
        
        $this->validator->validateCreate(['name' => '']);
    }

    public function testValidateCreateGeneratesCode(): void
    {
        $data = ['name' => 'Test'];
        $result = $this->validator->validateCreate($data);
        
        $this->assertStringStartsWith('DT', $result['code']);
    }

    public function testValidateCreatePreservesExistingCode(): void
    {
        $data = ['name' => 'Test', 'code' => 'CUSTOM'];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('CUSTOM', $result['code']);
    }

    public function testValidateCreateThrowsOnInvalidPhone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone number');
        
        $this->validator->validateCreate(['name' => 'Test', 'phone' => '123']);
    }

    public function testValidateCreateAcceptsValidPhone(): void
    {
        $data = ['name' => 'Test', 'phone' => '0901234567'];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('0901234567', $result['phone']);
    }

    public function testValidateCreateThrowsOnInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');
        
        $this->validator->validateCreate(['name' => 'Test', 'email' => 'invalid']);
    }

    public function testValidateCreateAcceptsValidEmail(): void
    {
        $data = ['name' => 'Test', 'email' => 'test@example.com'];
        $result = $this->validator->validateCreate($data);
        
        $this->assertEquals('test@example.com', $result['email']);
    }

    public function testValidateCreateThrowsOnInvalidGroupName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid group_name');
        
        $this->validator->validateCreate(['name' => 'Test', 'group_name' => 'invalid']);
    }

    public function testValidateCreateAcceptsValidGroupNames(): void
    {
        $validGroups = ['individual', 'company', 'staff'];
        foreach ($validGroups as $group) {
            $data = ['name' => 'Test', 'group_name' => $group];
            $result = $this->validator->validateCreate($data);
            $this->assertEquals($group, $result['group_name']);
        }
    }

    public function testValidateUpdateSuccess(): void
    {
        $data = ['name' => 'Updated'];
        $result = $this->validator->validateUpdate($data);
        
        $this->assertEquals('Updated', $result['name']);
    }

    public function testValidateUpdateThrowsOnInvalidPhone(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid phone number');
        
        $this->validator->validateUpdate(['phone' => '123']);
    }

    public function testValidateUpdateThrowsOnInvalidEmail(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');
        
        $this->validator->validateUpdate(['email' => 'invalid']);
    }

    public function testValidateUpdateThrowsOnInvalidGroupName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid group_name');
        
        $this->validator->validateUpdate(['group_name' => 'invalid']);
    }

    public function testValidateUpdateAllowsEmptyPhone(): void
    {
        $data = ['name' => 'Test', 'phone' => ''];
        $result = $this->validator->validateUpdate($data);
        
        $this->assertEquals('', $result['phone']);
    }

    public function testValidateUpdateAllowsEmptyEmail(): void
    {
        $data = ['name' => 'Test', 'email' => ''];
        $result = $this->validator->validateUpdate($data);
        
        $this->assertEquals('', $result['email']);
    }
}
