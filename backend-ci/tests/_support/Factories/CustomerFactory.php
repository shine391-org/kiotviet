<?php

namespace Tests\Support\Factories;

/**
 * Factory for creating customer test data
 * 
 * @agent-factory: Customer entity factory
 * @agent-pattern: Factory pattern for customer test data
 * @agent-reusable: HIGH
 */
class CustomerFactory extends BaseFactory
{
    protected static string $table = 'customers';
    
    protected static array $defaultAttributes = [
        'organization_id' => 1,
        'customer_group_id' => null,
        'name' => 'Test Customer',
        'email' => null, // Will be generated
        'phone' => null, // Will be generated
        'phone2' => null,
        'gender' => null,
        'facebook' => null,
        'customer_type' => 'INDIVIDUAL',
        'company_name' => null,
        'tax_code' => null,
        'buyer_name' => null,
        'invoice_company_name' => null,
        'invoice_address' => null,
        'invoice_province' => null,
        'invoice_district' => null,
        'invoice_ward' => null,
        'invoice_email' => null,
        'invoice_phone' => null,
        'cccd_cmnd' => null,
        'id_number' => null,
        'bank_account' => null,
        'bank_name' => null,
        'notes' => null,
        'code' => null, // Will be generated
        'address' => null,
        'province' => null,
        'district' => null,
        'ward' => null,
        'birthday' => null,
        'created_by' => null,
        'status' => 'active',
        'last_transaction_at' => null,
        'current_debt' => 0,
        'total_sales' => 0,
        'total_sales_net' => 0,
        'created_at' => null, // Will be set automatically
        'updated_at' => null, // Will be set automatically
        'deleted_at' => null,
    ];
    
    /**
     * Create a customer with specific attributes
     * 
     * @param array $attributes Override attributes
     * @return int Customer ID
     */
    public static function create(array $attributes = []): int
    {
        // Generate code if not provided
        if (!isset($attributes['code'])) {
            $attributes['code'] = static::generateCode('CUST');
        }
        
        // Generate email if not provided
        if (!isset($attributes['email'])) {
            $attributes['email'] = 'customer' . uniqid() . '@example.com';
        }
        
        // Generate phone if not provided
        if (!isset($attributes['phone'])) {
            $attributes['phone'] = '09' . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        }
        
        return parent::create($attributes);
    }
    
    /**
     * Create multiple customers
     * 
     * @param int $count Number of customers to create
     * @param array $attributes Override attributes
     * @return array Array of customer IDs
     */
    public static function createMany(int $count, array $attributes = []): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            // Generate unique data for each customer
            $customerAttributes = array_merge($attributes, [
                'name' => ($attributes['name'] ?? 'Test Customer') . ' ' . ($i + 1),
                'email' => isset($attributes['email']) ? null : 'customer' . uniqid() . $i . '@example.com',
                'phone' => isset($attributes['phone']) ? null : '09' . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            ]);
            $ids[] = static::create($customerAttributes);
        }
        return $ids;
    }
    
    /**
     * Create an inactive customer
     * 
     * @param array $attributes Override attributes
     * @return int Customer ID
     */
    public static function createInactive(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'deleted_at' => date('Y-m-d H:i:s')
        ]));
    }
    
    /**
     * Create a customer with contacts (email and phone)
     * 
     * @param string $email Custom email
     * @param string $phone Custom phone
     * @param array $attributes Override attributes
     * @return int Customer ID
     */
    public static function createWithContacts(string $email = null, string $phone = null, array $attributes = []): int
    {
        $contactData = [];
        
        if ($email !== null) {
            $contactData['email'] = $email;
        }
        
        if ($phone !== null) {
            $contactData['phone'] = $phone;
        }
        
        return static::create(array_merge($attributes, $contactData));
    }
    
    /**
     * Create a company customer
     * 
     * @param string $companyName Company name
     * @param string $taxCode Tax code
     * @param array $attributes Override attributes
     * @return int Customer ID
     */
    public static function createCompany(string $companyName = null, string $taxCode = null, array $attributes = []): int
    {
        $companyData = [
            'customer_type' => 'COMPANY',
        ];
        
        if ($companyName !== null) {
            $companyData['company_name'] = $companyName;
        } else {
            $companyData['company_name'] = 'Test Company ' . uniqid();
        }
        
        if ($taxCode !== null) {
            $companyData['tax_code'] = $taxCode;
        } else {
            $companyData['tax_code'] = static::generateCode('TAX', 8);
        }
        
        return static::create(array_merge($attributes, $companyData));
    }
    
    /**
     * Create a household customer
     * 
     * @param array $attributes Override attributes
     * @return int Customer ID
     */
    public static function createHousehold(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'customer_type' => 'HOUSEHOLD'
        ]));
    }
    
    /**
     * Create a customer with invoice information
     * 
     * @param array $invoiceData Invoice information
     * @param array $attributes Override attributes
     * @return int Customer ID
     */
    public static function createWithInvoiceInfo(array $invoiceData, array $attributes = []): int
    {
        return static::create(array_merge($attributes, $invoiceData));
    }
    
    /**
     * Create a customer with bank information
     * 
     * @param string $bankAccount Bank account number
     * @param string $bankName Bank name
     * @param array $attributes Override attributes
     * @return int Customer ID
     */
    public static function createWithBankInfo(string $bankAccount = null, string $bankName = null, array $attributes = []): int
    {
        $bankData = [];
        
        if ($bankAccount !== null) {
            $bankData['bank_account'] = $bankAccount;
        } else {
            $bankData['bank_account'] = '1234567890' . uniqid();
        }
        
        if ($bankName !== null) {
            $bankData['bank_name'] = $bankName;
        } else {
            $bankData['bank_name'] = 'Test Bank';
        }
        
        return static::create(array_merge($attributes, $bankData));
    }
    
    /**
     * Create a customer with gender
     * 
     * @param string $gender Gender (MALE, FEMALE, OTHER)
     * @param array $attributes Override attributes
     * @return int Customer ID
     */
    public static function createWithGender(string $gender, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'gender' => $gender
        ]));
    }
    
    /**
     * Create a customer in a specific group
     * 
     * @param int $customerGroupId Customer group ID
     * @param array $attributes Override attributes
     * @return int Customer ID
     */
    public static function createInGroup(int $customerGroupId, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'customer_group_id' => $customerGroupId
        ]));
    }
    
    /**
     * Create a customer in a specific organization
     * 
     * @param int $organizationId Organization ID
     * @param array $attributes Override attributes
     * @return int Customer ID
     */
    public static function createInOrganization(int $organizationId, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'organization_id' => $organizationId
        ]));
    }
}