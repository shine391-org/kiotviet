<?php

namespace Tests\Validators;

use App\Validators\ShareValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: ShareValidator
 * @agent-pattern: ACL payload validation
 */
class ShareValidatorTest extends CIUnitTestCase
{
    private ShareValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ShareValidator();
    }

    public function testValidateShareNormalizesInput(): void
    {
        $result = $this->validator->validateShare([
            'company_id' => '12',
            'entity_type' => 'order',
            'entity_id' => '33',
            'shared_with_role' => ' manager ',
            'permissions' => 'read,write',
            'created_by' => '7',
        ]);

        $this->assertSame(12, $result['company_id']);
        $this->assertSame(33, $result['entity_id']);
        $this->assertNull($result['shared_with_user_id']);
        $this->assertSame('manager', $result['shared_with_role']);
        $this->assertSame(['read', 'write'], $result['permissions']);
        $this->assertSame(7, $result['created_by']);
    }

    public function testValidateShareRequiresTarget(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateShare([
            'company_id' => 1,
            'entity_type' => 'doc',
            'entity_id' => 2,
            'permissions' => ['read'],
        ]);
    }

    public function testValidateListFiltersCastsIds(): void
    {
        $result = $this->validator->validateListFilters([
            'company_id' => '5',
            'entity_id' => '10',
            'shared_with_user_id' => '15',
            'entity_type' => 'order',
        ]);

        $this->assertSame(5, $result['company_id']);
        $this->assertSame(10, $result['entity_id']);
        $this->assertSame(15, $result['shared_with_user_id']);
        $this->assertSame('order', $result['entity_type']);
    }

    public function testValidateUnshareRequiresPositiveId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateUnshare(0);
    }
}
