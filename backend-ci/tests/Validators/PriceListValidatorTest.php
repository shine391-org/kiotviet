<?php

namespace Tests\Validators;

use App\Validators\PriceListValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Database\PriceListSchemaTrait;

/**
 * @agent-test: PriceListValidator
 * @agent-pattern: Validation rules coverage
 */
class PriceListValidatorTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    use PriceListSchemaTrait;

    private array $groupIds = [];
    private PriceListValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
        $this->resetPriceListSchema();

        $this->groupIds = [
            $this->createGroup('GRP-1', 'Group 1'),
            $this->createGroup('GRP-2', 'Group 2'),
        ];

        $this->validator = new PriceListValidator();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function testValidateCreateCastsFlagsAndGroups(): void
    {
        $result = $this->validator->validateCreate([
            'name' => 'Retail',
            'type' => 'retail',
            'apply_to_groups' => [$this->groupIds[0], (string) $this->groupIds[1]],
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31',
            'is_active' => true,
            'auto_update' => true,
            'base_price_list_id' => 2,
        ]);

        $this->assertSame([1, 2], $result['apply_to_groups']);
        $this->assertTrue($result['is_active']);
        $this->assertTrue($result['auto_update']);
        $this->assertSame(0, $result['priority']); // defaulted
    }

    public function testValidateCreateRejectsInvalidGroup(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'name' => 'VIP',
            'apply_to_groups' => [99],
        ]);
    }

    public function testValidateCreateRejectsInvalidDateRange(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateCreate([
            'name' => 'Bad date',
            'start_date' => '2025-12-31',
            'end_date' => '2025-01-01',
        ]);
    }

    public function testValidateUpdateRequiresFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateUpdate([]);
    }

    public function testValidateItemsRejectsNegativeValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateItems([
            ['product_id' => 1, 'price' => -5],
        ]);
    }

    private function createGroup(string $code, string $name): int
    {
        $this->db->table('customer_groups')->insert([
            'code' => $code,
            'name_vi' => $name,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->db->insertID();
    }
}
