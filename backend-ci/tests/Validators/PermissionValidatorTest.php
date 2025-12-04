<?php

namespace Tests\Validators;

use App\Validators\PermissionValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

/**
 * @agent-test: PermissionValidator
 * @agent-pattern: Permission normalization coverage
 */
class PermissionValidatorTest extends CIUnitTestCase
{
    private PermissionValidator $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new PermissionValidator();
    }

    public function testNormalizePermissionsDeduplicatesAndCasts(): void
    {
        $result = $this->validator->normalizePermissions('read, Write ,admin,read');

        $this->assertSame(['read', 'write', 'admin'], $result);
    }

    public function testNormalizePermissionsRejectsInvalidEntries(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->normalizePermissions(['read', 'unknown']);
    }

    public function testValidateRequiredCastsAndValidates(): void
    {
        $this->assertSame('write', $this->validator->validateRequired(' write '));
        $this->assertNull($this->validator->validateRequired(null));
    }

    public function testValidateRequiredRejectsUnknownPermission(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->validateRequired('execute');
    }

    public function testPositiveIntRequiresNumericAndPositive(): void
    {
        $this->assertSame(5, $this->validator->positiveInt('5', 'company_id'));

        $this->expectException(InvalidArgumentException::class);
        $this->validator->positiveInt(0, 'company_id');
    }
}
