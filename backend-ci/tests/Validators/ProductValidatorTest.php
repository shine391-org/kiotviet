<?php

namespace Tests\Validators;

use App\Repositories\ProductVariants\ProductVariantRepository;
use App\Repositories\Products\ProductRepository;
use App\Validators\ProductValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: ProductValidator
 * @agent-pattern: Validator unit coverage
 */
class ProductValidatorTest extends CIUnitTestCase
{
    private ProductValidator $validator;
    private StubProductRepo $productRepo;
    private StubVariantRepo $variantRepo;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productRepo = new StubProductRepo();
        $this->variantRepo = new StubVariantRepo();
        $this->validator = new ProductValidator(null, $this->productRepo, $this->variantRepo);
    }

    public function testValidateListFiltersCastsValues(): void
    {
        $result = $this->validator->validateListFilters([
            'page' => '2',
            'limit' => '50',
            'include_variants' => 'true',
            'price_list_id' => '3',
        ]);

        $this->assertSame(2, $result['page']);
        $this->assertSame(50, $result['limit']);
        $this->assertTrue($result['include_variants']);
        $this->assertSame(3, $result['price_list_id']);
    }

    public function testValidateCreateChecksUniqueCode(): void
    {
        $data = ['code' => 'SKU-1', 'name' => 'Test'];

        $validated = $this->validator->validateCreate($data);

        $this->assertSame('SKU-1', $validated['code']);
        $this->assertSame('Test', $validated['name']);
    }

    public function testValidateCreateThrowsWhenCodeExists(): void
    {
        $this->productRepo->existingCodes = ['DUP'];
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate(['code' => ' DUP ', 'name' => 'Test']);
    }

    public function testValidateUpdateRequiresFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateUpdate(1, []);
    }

    public function testValidateImageIdsCastsToInts(): void
    {
        $result = $this->validator->validateImageIds(['1', 2, '3']);

        $this->assertSame([1, 2, 3], $result);
    }

    public function testValidateImageIdsThrowsOnEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateImageIds([]);
    }
}

class StubProductRepo extends ProductRepository
{
    public array $existingCodes = [];
    public function __construct() {}
    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        return in_array($code, $this->existingCodes, true);
    }
}

class StubVariantRepo extends ProductVariantRepository
{
    public function __construct() {}
    public function skuExists(string $sku, ?int $excludeVariantId = null): bool
    {
        return false;
    }
}
