<?php

namespace Tests\Unit;

use App\Libraries\PolymorphicValidator;
use CodeIgniter\Test\CIUnitTestCase;
use InvalidArgumentException;

class PolymorphicValidatorTest extends CIUnitTestCase
{
    public function testRejectsUnknownContext(): void
    {
        $validator = new PolymorphicValidator();
        $this->expectException(InvalidArgumentException::class);
        $validator->validate('unknown', 'order');
    }

    public function testRejectsUnsupportedType(): void
    {
        $validator = new PolymorphicValidator();
        $this->expectException(InvalidArgumentException::class);
        $validator->validate('approvals', 'unsupported');
    }

    public function testAcceptsKnownTypeWithoutId(): void
    {
        $validator = new PolymorphicValidator();
        // Không ném exception cho type hợp lệ, id trống
        $validator->validate('approvals', 'order', null);
        $this->assertTrue(true);
    }
}
