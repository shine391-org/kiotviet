<?php

namespace Tests\Validators;

use App\Validators\ProductMediaDateValidator;
use CodeIgniter\Test\CIUnitTestCase;

class ProductMediaDateValidatorTest extends CIUnitTestCase
{
    private ProductMediaDateValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ProductMediaDateValidator();
    }

    public function testValidateFiltersDefaults(): void
    {
        $result = $this->validator->validateFilters([]);
        $this->assertSame(20, $result['limit']);
        $this->assertSame(0, $result['offset']);
        $this->assertNull($result['year']);
        $this->assertNull($result['month']);
        $this->assertNull($result['entity_id']);
    }

    public function testValidateFiltersValidInput(): void
    {
        $input = [
            'year' => 2023,
            'month' => 10,
            'entity_id' => 5,
            'limit' => 50,
            'offset' => 10,
        ];
        $result = $this->validator->validateFilters($input);
        $this->assertSame(2023, $result['year']);
        $this->assertSame(10, $result['month']);
        $this->assertSame(5, $result['entity_id']);
        $this->assertSame(50, $result['limit']);
        $this->assertSame(10, $result['offset']);
    }

    public function testValidateFiltersInvalidYear(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->validator->validateFilters(['year' => 1900]); // < 1970
    }

    public function testValidateFiltersInvalidMonth(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->validator->validateFilters(['month' => 13]);
    }

    public function testValidateFiltersInvalidLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->validator->validateFilters(['limit' => 201]);
    }
}
