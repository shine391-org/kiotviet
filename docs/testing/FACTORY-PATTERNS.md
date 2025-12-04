# Factory Patterns Guide

This guide provides comprehensive documentation for using test data factories in the LanoCRM backend testing suite.

## Overview

Factories provide a clean, consistent way to create test data for your unit and integration tests. They follow the Factory Pattern and extend from `BaseFactory` to ensure consistency across all test data creation.

## Available Factories

### 1. BaseFactory

All factories extend from `BaseFactory` which provides core functionality:

- **Database Connection**: Automatically connects to test database
- **Timestamps**: Automatically sets `created_at` and `updated_at`
- **Code Generation**: Provides `generateCode()` method for unique identifiers
- **Random Data**: Provides `randomFloat()` and `randomDate()` helpers
- **CRUD Operations**: Basic create, find, all, truncate operations

**Location**: `backend-ci/tests/_support/Factories/BaseFactory.php`

### 2. CustomerFactory

Creates customer records with comprehensive customer data including contact information, invoice details, and company information.

**Location**: `backend-ci/tests/_support/Factories/CustomerFactory.php`

#### Basic Usage

```php
// Create a simple customer
$customerId = CustomerFactory::create();

// Create with custom attributes
$customerId = CustomerFactory::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'phone' => '0912345678'
]);
```

#### Advanced Methods

```php
// Create multiple customers
$ids = CustomerFactory::createMany(5);

// Create inactive customer
$inactiveId = CustomerFactory::createInactive();

// Create customer with contacts
$contactId = CustomerFactory::createWithContacts('user@test.com', '0987654321');

// Create company customer
$companyId = CustomerFactory::createCompany('Acme Corp', '123456789');

// Create household customer
$householdId = CustomerFactory::createHousehold();

// Create customer with invoice info
$invoiceId = CustomerFactory::createWithInvoiceInfo([
    'invoice_company_name' => 'Acme Corp',
    'invoice_address' => '123 Main St'
]);

// Create customer with bank info
$bankId = CustomerFactory::createWithBankInfo('1234567890', 'Test Bank');

// Create customer with gender
$genderId = CustomerFactory::createWithGender('MALE');

// Create customer in specific group
$groupId = CustomerFactory::createInGroup(1);

// Create customer in specific organization
$orgId = CustomerFactory::createInOrganization(2);
```

#### Default Attributes

- `organization_id`: 1
- `customer_type`: 'INDIVIDUAL'
- `status`: 'active'
- Auto-generated: `code`, `email`, `phone`

### 3. OrderFactory

Creates order records with comprehensive order data including items, payments, and shipping information.

**Location**: `backend-ci/tests/_support/Factories/OrderFactory.php`

#### Basic Usage

```php
// Create a simple order
$orderId = OrderFactory::create();

// Create with custom attributes
$orderId = OrderFactory::create([
    'customer_id' => 1,
    'total' => 100.00,
    'status' => 'pending'
]);
```

#### Advanced Methods

```php
// Create multiple orders
$ids = OrderFactory::createMany(5);

// Create pending order
$pendingId = OrderFactory::createPending();

// Create completed order
$completedId = OrderFactory::createCompleted();

// Create order with items
$itemsId = OrderFactory::createWithItems([
    ['product_id' => 1, 'quantity' => 2, 'final_price' => 50.00],
    ['product_id' => 2, 'quantity' => 1, 'final_price' => 25.00]
]);

// Create order with specific amounts
$amountId = OrderFactory::createWithAmounts(100.00, 10.00);

// Create order for specific customer
$customerId = OrderFactory::createForCustomer(1);

// Create order in customer group
$groupId = OrderFactory::createInCustomerGroup(1);

// Create order with specific date
$dateId = OrderFactory::createWithDate('2024-01-15');

// Create order with price list
$priceListId = OrderFactory::createWithPriceList(1, 'Special Prices');

// Create cancelled order
$cancelledId = OrderFactory::createCancelled();

// Create processing order
$processingId = OrderFactory::createProcessing();

// Create shipped order
$shippedId = OrderFactory::createShipped();

// Create order with random amounts
$randomId = OrderFactory::createWithRandomAmounts(50.00, 500.00);

// Create order with random items
$randomItemsId = OrderFactory::createWithRandomItems(3);
```

#### Default Attributes

- `order_type`: 'online'
- `status`: 'draft'
- Auto-generated: `code`, `order_number`, `order_date`

### 4. UserFactory

Creates user records with comprehensive user data including authentication, roles, and permissions.

**Location**: `backend-ci/tests/_support/Factories/UserFactory.php`

#### Basic Usage

```php
// Create a simple user
$userId = UserFactory::create();

// Create with custom attributes
$userId = UserFactory::create([
    'username' => 'johndoe',
    'email' => 'john@example.com',
    'full_name' => 'John Doe'
]);
```

#### Advanced Methods

```php
// Create multiple users
$ids = UserFactory::createMany(5);

// Create admin user
$adminId = UserFactory::createAdmin();

// Create staff user
$staffId = UserFactory::createStaff();

// Create inactive user
$inactiveId = UserFactory::createInactive();

// Create suspended user
$suspendedId = UserFactory::createSuspended();

// Create user with 2FA
$twoFAId = UserFactory::createWith2FA();

// Create user in specific branch
$branchId = UserFactory::createInBranch(1);

// Create user with role
$roleId = UserFactory::createWithRole(1);

// Create user with failed attempts
$failedId = UserFactory::createWithFailedAttempts(3);

// Create locked user
$lockedId = UserFactory::createLocked();

// Create user with last login
$loginId = UserFactory::createWithLastLogin('2024-01-15 10:00:00', '192.168.1.100');

// Create user with remember token
$rememberId = UserFactory::createWithRememberToken();

// Create user with custom timezone
$timezoneId = UserFactory::createWithTimezone('UTC');

// Create user with avatar
$avatarId = UserFactory::createWithAvatar('avatars/user1.png');

// Create user with password change
$passwordChangeId = UserFactory::createWithPasswordChange('2024-01-10 08:00:00');
```

#### Default Attributes

- `status`: 'active'
- `timezone`: 'Asia/Ho_Chi_Minh'
- `two_factor_enabled`: 0
- Auto-generated: `username`, `email`, `password`, `phone`

## Best Practices

### 1. Use Specific Factory Methods

Instead of passing large arrays to `create()`, use the specific factory methods:

```php
// Good
$customerId = CustomerFactory::createCompany('Acme Corp', '123456789');

// Avoid
$customerId = CustomerFactory::create([
    'customer_type' => 'COMPANY',
    'company_name' => 'Acme Corp',
    'tax_code' => '123456789'
]);
```

### 2. Chain Factory Methods for Complex Scenarios

```php
// Create a customer with company info and bank details
$customerId = CustomerFactory::createCompany('Acme Corp', '123456789');
CustomerFactory::createWithBankInfo('1234567890', 'Test Bank', ['id' => $customerId]);
```

### 3. Use Factory Methods in Tests

```php
public function testOrderCreation()
{
    // Create test data
    $customerId = CustomerFactory::create();
    $productId = ProductFactory::create();
    
    // Create order with items
    $orderId = OrderFactory::createWithItems([
        ['product_id' => $productId, 'quantity' => 2, 'final_price' => 100.00]
    ], ['customer_id' => $customerId]);
    
    // Test assertions
    $this->assertNotNull($orderId);
    $order = OrderFactory::find($orderId);
    $this->assertEquals($customerId, $order['customer_id']);
}
```

### 4. Clean Up Test Data

Always clean up test data in tearDown:

```php
protected function tearDown(): void
{
    CustomerFactory::truncate();
    OrderFactory::truncate();
    UserFactory::truncate();
    parent::tearDown();
}
```

## Factory Method Reference

### Common Methods (Available in All Factories)

- `create($attributes = [])`: Create single record
- `createMany($count, $attributes = [])`: Create multiple records
- `find($id)`: Find record by ID
- `all($where = [])`: Get all records
- `truncate()`: Truncate table
- `deleteWhere($where)`: Delete records by conditions

### Helper Methods (From BaseFactory)

- `generateCode($prefix, $length = 6)`: Generate unique code
- `randomFloat($min = 0.0, $max = 999.99, $decimalPlaces = 2)`: Generate random float
- `randomDate($startDate = '-30 days', $endDate = 'now')`: Generate random date

## Integration with Testing Framework

Factories are designed to work seamlessly with:

1. **DevDatabaseTrait**: Automatic transaction rollback
2. **PHPUnit**: Standard test lifecycle
3. **CodeIgniter 4**: Database connection and query builder

Example test setup:

```php
use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Factories\CustomerFactory;
use Tests\Support\Factories\OrderFactory;

class OrderTest extends CIUnitTestCase
{
    use DevDatabaseTrait;
    
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }
    
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }
    
    public function testOrderCreation()
    {
        $customerId = CustomerFactory::create();
        $orderId = OrderFactory::create(['customer_id' => $customerId]);
        
        $this->assertNotNull($orderId);
    }
}
```

## Troubleshooting

### Common Issues

1. **Missing Tables**: Ensure TestSchemaSetup migration has run
2. **Foreign Key Constraints**: Create dependent records first
3. **Unique Constraints**: Use factory methods that generate unique values
4. **Data Type Mismatches**: Check factory default attributes match schema

### Debug Tips

```php
// Check what was actually created
$record = CustomerFactory::find($customerId);
var_dump($record);

// Check table structure
$db = \Config\Database::connect('tests');
$fields = $db->getFieldData('customers');
var_dump($fields);
```

## Contributing

When adding new factories:

1. Extend from `BaseFactory`
2. Follow naming conventions
3. Include comprehensive default attributes
4. Add specific helper methods for common scenarios
5. Update this documentation
6. Add tests for the new factory

## Related Documentation

- [Backend Testing Guide](BACKEND-TESTING.md)
- [Test Checklist](TEST-CHECKLIST.md)
- [Database Testing Patterns](../database/TESTING-PATTERNS.md)