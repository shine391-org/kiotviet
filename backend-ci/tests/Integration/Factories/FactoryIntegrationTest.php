<?php

namespace Tests\Integration\Factories;

use Tests\Support\Database\DevDatabaseTrait;
use Tests\Support\Factories\CustomerFactory;
use Tests\Support\Factories\OrderFactory;
use Tests\Support\Factories\UserFactory;
use Tests\Support\Factories\ProductFactory;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Integration tests for all factories
 * 
 * @agent-test: Factory integration tests
 * @agent-pattern: Integration testing for factories
 * @agent-reusable: HIGH
 */
class FactoryIntegrationTest extends CIUnitTestCase
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
    
    /**
     * Test CustomerFactory basic functionality
     */
    public function testCustomerFactoryBasic()
    {
        $customerId = CustomerFactory::create();
        
        $this->assertNotNull($customerId);
        $this->assertIsInt($customerId);
        
        $customer = CustomerFactory::find($customerId);
        $this->assertNotNull($customer);
        $this->assertEquals('Test Customer', $customer['name']);
        $this->assertNotNull($customer['code']);
        $this->assertNotNull($customer['email']);
        $this->assertNotNull($customer['phone']);
        $this->assertEquals('active', $customer['status']);
    }
    
    /**
     * Test CustomerFactory createMany
     */
    public function testCustomerFactoryCreateMany()
    {
        $ids = CustomerFactory::createMany(5);
        
        $this->assertCount(5, $ids);
        $this->assertContainsOnlyInts($ids);
        
        $customers = CustomerFactory::all();
        $this->assertGreaterThanOrEqual(5, count($customers));
    }
    
    /**
     * Test CustomerFactory specialized methods
     */
    public function testCustomerFactorySpecializedMethods()
    {
        // Test createInactive
        $inactiveId = CustomerFactory::createInactive();
        $inactiveCustomer = CustomerFactory::find($inactiveId);
        $this->assertNotNull($inactiveCustomer['deleted_at']);
        
        // Test createCompany
        $companyId = CustomerFactory::createCompany('Test Company', 'TAX123');
        $companyCustomer = CustomerFactory::find($companyId);
        $this->assertEquals('COMPANY', $companyCustomer['customer_type']);
        $this->assertEquals('Test Company', $companyCustomer['company_name']);
        $this->assertEquals('TAX123', $companyCustomer['tax_code']);
        
        // Test createHousehold
        $householdId = CustomerFactory::createHousehold();
        $householdCustomer = CustomerFactory::find($householdId);
        $this->assertEquals('HOUSEHOLD', $householdCustomer['customer_type']);
        
        // Test createWithContacts
        $contactId = CustomerFactory::createWithContacts('test@example.com', '0912345678');
        $contactCustomer = CustomerFactory::find($contactId);
        $this->assertEquals('test@example.com', $contactCustomer['email']);
        $this->assertEquals('0912345678', $contactCustomer['phone']);
        
        // Test createWithBankInfo
        $bankId = CustomerFactory::createWithBankInfo('1234567890', 'Test Bank');
        $bankCustomer = CustomerFactory::find($bankId);
        $this->assertEquals('1234567890', $bankCustomer['bank_account']);
        $this->assertEquals('Test Bank', $bankCustomer['bank_name']);
        
        // Test createWithGender
        $genderId = CustomerFactory::createWithGender('MALE');
        $genderCustomer = CustomerFactory::find($genderId);
        $this->assertEquals('MALE', $genderCustomer['gender']);
    }
    
    /**
     * Test OrderFactory basic functionality
     */
    public function testOrderFactoryBasic()
    {
        $orderId = OrderFactory::create();
        
        $this->assertNotNull($orderId);
        $this->assertIsInt($orderId);
        
        $order = OrderFactory::find($orderId);
        $this->assertNotNull($order);
        $this->assertEquals('draft', $order['status']);
        $this->assertNotNull($order['code']);
        $this->assertNotNull($order['order_number']);
        $this->assertNotNull($order['order_date']);
    }
    
    /**
     * Test OrderFactory createMany
     */
    public function testOrderFactoryCreateMany()
    {
        $ids = OrderFactory::createMany(3);
        
        $this->assertCount(3, $ids);
        $this->assertContainsOnlyInts($ids);
        
        $orders = OrderFactory::all();
        $this->assertGreaterThanOrEqual(3, count($orders));
    }
    
    /**
     * Test OrderFactory specialized methods
     */
    public function testOrderFactorySpecializedMethods()
    {
        // Test createPending
        $pendingId = OrderFactory::createPending();
        $pendingOrder = OrderFactory::find($pendingId);
        $this->assertEquals('pending', $pendingOrder['status']);
        
        // Test createCompleted
        $completedId = OrderFactory::createCompleted();
        $completedOrder = OrderFactory::find($completedId);
        $this->assertEquals('completed', $completedOrder['status']);
        
        // Test createCancelled
        $cancelledId = OrderFactory::createCancelled();
        $cancelledOrder = OrderFactory::find($cancelledId);
        $this->assertEquals('cancelled', $cancelledOrder['status']);
        
        // Test createProcessing
        $processingId = OrderFactory::createProcessing();
        $processingOrder = OrderFactory::find($processingId);
        $this->assertEquals('processing', $processingOrder['status']);
        
        // Test createShipped
        $shippedId = OrderFactory::createShipped();
        $shippedOrder = OrderFactory::find($shippedId);
        $this->assertEquals('shipped', $shippedOrder['status']);
    }
    
    /**
     * Test OrderFactory with items
     */
    public function testOrderFactoryWithItems()
    {
        $productId = ProductFactory::create();
        
        $orderId = OrderFactory::createWithItems([
            ['product_id' => $productId, 'quantity' => 2, 'final_price' => 100.00],
            ['product_id' => $productId, 'quantity' => 1, 'final_price' => 50.00]
        ]);
        
        $this->assertNotNull($orderId);
        
        $order = OrderFactory::find($orderId);
        $this->assertEquals(250.00, $order['subtotal']);
        $this->assertEquals(250.00, $order['total']);
        
        // Check order items
        $db = \Config\Database::connect('tests');
        $orderItems = $db->table('order_items')
                          ->where('order_id', $orderId)
                          ->get()
                          ->getResultArray();
        
        $this->assertCount(2, $orderItems);
    }
    
    /**
     * Test OrderFactory with customer
     */
    public function testOrderFactoryWithCustomer()
    {
        $customerId = CustomerFactory::create();
        $orderId = OrderFactory::create(['customer_id' => $customerId]);
        
        $order = OrderFactory::find($orderId);
        $this->assertEquals($customerId, $order['customer_id']);
    }
    
    /**
     * Test OrderFactory with amounts
     */
    public function testOrderFactoryWithAmounts()
    {
        $orderId = OrderFactory::createWithAmounts(100.00, 10.00);
        
        $order = OrderFactory::find($orderId);
        $this->assertEquals(100.00, $order['subtotal']);
        $this->assertEquals(10.00, $order['discount_total']);
        $this->assertEquals(90.00, $order['total']);
    }
    
    /**
     * Test UserFactory basic functionality
     */
    public function testUserFactoryBasic()
    {
        $userId = UserFactory::create();
        
        $this->assertNotNull($userId);
        $this->assertIsInt($userId);
        
        $user = UserFactory::find($userId);
        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user['full_name']);
        $this->assertNotNull($user['username']);
        $this->assertNotNull($user['email']);
        $this->assertNotNull($user['password']);
        $this->assertEquals('active', $user['status']);
    }
    
    /**
     * Test UserFactory createMany
     */
    public function testUserFactoryCreateMany()
    {
        $ids = UserFactory::createMany(4);
        
        $this->assertCount(4, $ids);
        $this->assertContainsOnlyInts($ids);
        
        $users = UserFactory::all();
        $this->assertGreaterThanOrEqual(4, count($users));
    }
    
    /**
     * Test UserFactory specialized methods
     */
    public function testUserFactorySpecializedMethods()
    {
        // Test createAdmin
        $adminId = UserFactory::createAdmin();
        $adminUser = UserFactory::find($adminId);
        $this->assertEquals('active', $adminUser['status']);
        $this->assertStringContains('admin', $adminUser['username']);
        $this->assertStringContains('admin', $adminUser['email']);
        
        // Test createStaff
        $staffId = UserFactory::createStaff();
        $staffUser = UserFactory::find($staffId);
        $this->assertEquals('active', $staffUser['status']);
        $this->assertStringContains('staff', $staffUser['username']);
        $this->assertStringContains('staff', $staffUser['email']);
        
        // Test createInactive
        $inactiveId = UserFactory::createInactive();
        $inactiveUser = UserFactory::find($inactiveId);
        $this->assertEquals('inactive', $inactiveUser['status']);
        
        // Test createSuspended
        $suspendedId = UserFactory::createSuspended();
        $suspendedUser = UserFactory::find($suspendedId);
        $this->assertEquals('suspended', $suspendedUser['status']);
        
        // Test createWith2FA
        $twoFAId = UserFactory::createWith2FA();
        $twoFAUser = UserFactory::find($twoFAId);
        $this->assertEquals(1, $twoFAUser['two_factor_enabled']);
        $this->assertNotNull($twoFAUser['two_factor_secret']);
        
        // Test createWithFailedAttempts
        $failedId = UserFactory::createWithFailedAttempts(3);
        $failedUser = UserFactory::find($failedId);
        $this->assertEquals(3, $failedUser['failed_login_attempts']);
        
        // Test createLocked
        $lockedId = UserFactory::createLocked();
        $lockedUser = UserFactory::find($lockedId);
        $this->assertEquals('suspended', $lockedUser['status']);
        $this->assertEquals(5, $lockedUser['failed_login_attempts']);
        $this->assertNotNull($lockedUser['account_locked_until']);
    }
    
    /**
     * Test UserFactory with last login
     */
    public function testUserFactoryWithLastLogin()
    {
        $userId = UserFactory::createWithLastLogin('2024-01-15 10:00:00', '192.168.1.100');
        
        $user = UserFactory::find($userId);
        $this->assertEquals('2024-01-15 10:00:00', $user['last_login_at']);
        $this->assertEquals('192.168.1.100', $user['last_login_ip']);
    }
    
    /**
     * Test UserFactory with remember token
     */
    public function testUserFactoryWithRememberToken()
    {
        $userId = UserFactory::createWithRememberToken();
        
        $user = UserFactory::find($userId);
        $this->assertNotNull($user['remember_token']);
        $this->assertStringStartsWith('remember_', $user['remember_token']);
    }
    
    /**
     * Test UserFactory with timezone
     */
    public function testUserFactoryWithTimezone()
    {
        $userId = UserFactory::createWithTimezone('UTC');
        
        $user = UserFactory::find($userId);
        $this->assertEquals('UTC', $user['timezone']);
    }
    
    /**
     * Test UserFactory with avatar
     */
    public function testUserFactoryWithAvatar()
    {
        $userId = UserFactory::createWithAvatar('avatars/test.png');
        
        $user = UserFactory::find($userId);
        $this->assertEquals('avatars/test.png', $user['avatar']);
    }
    
    /**
     * Test factory data integrity
     */
    public function testFactoryDataIntegrity()
    {
        // Create related data
        $customerId = CustomerFactory::create();
        $userId = UserFactory::create();
        $productId = ProductFactory::create();
        
        // Create order with customer
        $orderId = OrderFactory::create([
            'customer_id' => $customerId
        ]);
        
        // Verify relationships
        $order = OrderFactory::find($orderId);
        $this->assertEquals($customerId, $order['customer_id']);
        
        $customer = CustomerFactory::find($customerId);
        $this->assertNotNull($customer);
        
        $user = UserFactory::find($userId);
        $this->assertNotNull($user);
        
        $product = ProductFactory::find($productId);
        $this->assertNotNull($product);
    }
    
    /**
     * Test factory cleanup
     */
    public function testFactoryCleanup()
    {
        // Create some data
        CustomerFactory::createMany(3);
        OrderFactory::createMany(2);
        UserFactory::createMany(4);
        
        // Verify data exists
        $this->assertGreaterThan(0, count(CustomerFactory::all()));
        $this->assertGreaterThan(0, count(OrderFactory::all()));
        $this->assertGreaterThan(0, count(UserFactory::all()));
        
        // Clean up
        CustomerFactory::truncate();
        OrderFactory::truncate();
        UserFactory::truncate();
        
        // Verify data is gone
        $this->assertEmpty(CustomerFactory::all());
        $this->assertEmpty(OrderFactory::all());
        $this->assertEmpty(UserFactory::all());
    }
    
    /**
     * Test factory unique constraints
     */
    public function testFactoryUniqueConstraints()
    {
        // Create multiple customers and verify unique codes
        $ids = CustomerFactory::createMany(3);
        $customers = [];
        
        foreach ($ids as $id) {
            $customer = CustomerFactory::find($id);
            $customers[] = $customer;
        }
        
        $codes = array_column($customers, 'code');
        $this->assertCount(3, array_unique($codes));
        
        // Create multiple orders and verify unique order numbers
        $orderIds = OrderFactory::createMany(3);
        $orders = [];
        
        foreach ($orderIds as $id) {
            $order = OrderFactory::find($id);
            $orders[] = $order;
        }
        
        $orderNumbers = array_column($orders, 'order_number');
        $this->assertCount(3, array_unique($orderNumbers));
        
        // Create multiple users and verify unique usernames/emails
        $userIds = UserFactory::createMany(3);
        $users = [];
        
        foreach ($userIds as $id) {
            $user = UserFactory::find($id);
            $users[] = $user;
        }
        
        $usernames = array_column($users, 'username');
        $emails = array_column($users, 'email');
        $this->assertCount(3, array_unique($usernames));
        $this->assertCount(3, array_unique($emails));
    }
}