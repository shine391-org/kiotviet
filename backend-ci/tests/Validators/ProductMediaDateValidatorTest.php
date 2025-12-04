<?php

namespace Tests\Validators;

use App\Validators\ProductMediaDateValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: ProductMediaDateValidator
 * @agent-pattern: Validator unit coverage
 */
class ProductMediaDateValidatorTest extends CIUnitTestCase
{
    private ProductMediaDateValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ProductMediaDateValidator();
    }

    public function testValidateFiltersCastsAndDefaults(): void
    {
        $result = $this->validator->validateFilters([
            'year' => '2025',
            'month' => '12',
            'entity_id' => '5',
            'limit' => '50',
            'offset' => '10',
        ]);

        $this->assertSame(2025, $result['year']);
        $this->assertSame(12, $result['month']);
        $this->assertSame(5, $result['entity_id']);
        $this->assertSame(50, $result['limit']);
        $this->assertSame(10, $result['offset']);
    }

    public function testValidateFiltersUsesDefaultsWhenMissing(): void
    {
        $result = $this->validator->validateFilters([]);

        $this->assertNull($result['year']);
        $this->assertNull($result['month']);
        $this->assertNull($result['entity_id']);
        $this->assertSame(20, $result['limit']);
        $this->assertSame(0, $result['offset']);
    }

    public function testValidateFiltersRejectsInvalidMonth(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateFilters(['month' => 13]);
    }
}
