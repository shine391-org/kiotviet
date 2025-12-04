<?php

namespace Tests\Validators;

use App\Validators\ProductMediaValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: ProductMediaValidator
 * @agent-pattern: Validator unit coverage
 */
class ProductMediaValidatorTest extends CIUnitTestCase
{
    private ProductMediaValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ProductMediaValidator();
    }

    public function testValidateLibraryFiltersCastsValues(): void
    {
        $result = $this->validator->validateLibraryFilters([
            'limit' => '40',
            'offset' => '2',
            'entity_id' => '9',
        ]);

        $this->assertSame(40, $result['limit']);
        $this->assertSame(2, $result['offset']);
        $this->assertSame(9, $result['entity_id']);
    }

    public function testValidateLibraryFiltersUsesDefaults(): void
    {
        $result = $this->validator->validateLibraryFilters([]);

        $this->assertSame(20, $result['limit']);
        $this->assertSame(0, $result['offset']);
        $this->assertNull($result['entity_id']);
    }

    public function testValidateLibraryFiltersRejectsBadLimit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateLibraryFilters(['limit' => 0]);
    }
}
