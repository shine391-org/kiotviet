<?php

namespace Tests\Support\Factories;

/**
 * Factory for creating user test data
 * 
 * @agent-factory: User entity factory
 * @agent-pattern: Factory pattern for user test data
 * @agent-reusable: HIGH
 */
class UserFactory extends BaseFactory
{
    protected static string $table = 'users';
    
    protected static array $defaultAttributes = [
        'username' => null, // Will be generated
        'email' => null, // Will be generated
        'password' => null, // Will be generated
        'full_name' => 'Test User',
        'phone' => null, // Will be generated
        'avatar' => null,
        'branch_id' => null,
        'status' => 'active',
        'last_login_at' => null,
        'last_login_ip' => null,
        'remember_token' => null,
        'two_factor_secret' => null,
        'two_factor_enabled' => 0,
        'password_changed_at' => null,
        'failed_login_attempts' => 0,
        'account_locked_until' => null,
        'timezone' => 'Asia/Ho_Chi_Minh',
        'created_at' => null, // Will be set automatically
        'updated_at' => null, // Will be set automatically
        'deleted_at' => null,
    ];
    
    /**
     * Create a user with specific attributes
     * 
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function create(array $attributes = []): int
    {
        // Generate username if not provided
        if (!isset($attributes['username'])) {
            $attributes['username'] = 'user' . uniqid();
        }
        
        // Generate email if not provided
        if (!isset($attributes['email'])) {
            $attributes['email'] = 'user' . uniqid() . '@example.com';
        }
        
        // Generate password if not provided
        if (!isset($attributes['password'])) {
            $attributes['password'] = password_hash('password123', PASSWORD_DEFAULT);
        }
        
        // Generate phone if not provided
        if (!isset($attributes['phone'])) {
            $attributes['phone'] = '09' . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT);
        }
        
        return parent::create($attributes);
    }
    
    /**
     * Create multiple users
     * 
     * @param int $count Number of users to create
     * @param array $attributes Override attributes
     * @return array Array of user IDs
     */
    public static function createMany(int $count, array $attributes = []): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            // Generate unique data for each user
            $userAttributes = array_merge($attributes, [
                'username' => isset($attributes['username']) ? null : 'user' . uniqid() . $i,
                'email' => isset($attributes['email']) ? null : 'user' . uniqid() . $i . '@example.com',
                'full_name' => ($attributes['full_name'] ?? 'Test User') . ' ' . ($i + 1),
                'phone' => isset($attributes['phone']) ? null : '09' . str_pad(mt_rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
            ]);
            $ids[] = static::create($userAttributes);
        }
        return $ids;
    }
    
    /**
     * Create an admin user
     * 
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function createAdmin(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'username' => 'admin' . uniqid(),
            'email' => 'admin' . uniqid() . '@example.com',
            'full_name' => 'Admin User',
            'status' => 'active'
        ]));
    }
    
    /**
     * Create a staff user
     * 
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function createStaff(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'username' => 'staff' . uniqid(),
            'email' => 'staff' . uniqid() . '@example.com',
            'full_name' => 'Staff User',
            'status' => 'active'
        ]));
    }
    
    /**
     * Create an inactive user
     * 
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function createInactive(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'status' => 'inactive'
        ]));
    }
    
    /**
     * Create a suspended user
     * 
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function createSuspended(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'status' => 'suspended'
        ]));
    }
    
    /**
     * Create a user with two-factor authentication enabled
     * 
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function createWith2FA(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'two_factor_enabled' => 1,
            'two_factor_secret' => 'TEST2FASECRET' . uniqid()
        ]));
    }
    
    /**
     * Create a user with specific branch
     * 
     * @param int $branchId Branch ID
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function createInBranch(int $branchId, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'branch_id' => $branchId
        ]));
    }
    
    /**
     * Create a user with specific role (requires role assignment)
     * 
     * @param int $roleId Role ID
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function createWithRole(int $roleId, array $attributes = []): int
    {
        $userId = static::create($attributes);
        
        // Assign role to user
        $db = static::getDb();
        $db->table('model_has_roles')->insert([
            'role_id' => $roleId,
            'model_type' => 'App\\Models\\User',
            'model_id' => $userId
        ]);
        
        return $userId;
    }
    
    /**
     * Create a user with failed login attempts
     * 
     * @param int $failedAttempts Number of failed attempts
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function createWithFailedAttempts(int $failedAttempts = 3, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'failed_login_attempts' => $failedAttempts
        ]));
    }
    
    /**
     * Create a locked user
     * 
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function createLocked(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'status' => 'suspended',
            'failed_login_attempts' => 5,
            'account_locked_until' => date('Y-m-d H:i:s', strtotime('+1 hour'))
        ]));
    }
    
    /**
     * Create a user with last login information
     * 
     * @param string $lastLoginAt Last login datetime
     * @param string $lastLoginIp Last login IP
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function createWithLastLogin(string $lastLoginAt = null, string $lastLoginIp = null, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'last_login_at' => $lastLoginAt ?: date('Y-m-d H:i:s', strtotime('-1 day')),
            'last_login_ip' => $lastLoginIp ?: '192.168.1.100'
        ]));
    }
    
    /**
     * Create a user with remember token
     * 
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function createWithRememberToken(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'remember_token' => 'remember_' . uniqid()
        ]));
    }
    
    /**
     * Create a user with custom timezone
     * 
     * @param string $timezone Timezone
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function createWithTimezone(string $timezone, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'timezone' => $timezone
        ]));
    }
    
    /**
     * Create a user with avatar
     * 
     * @param string $avatar Avatar path/URL
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function createWithAvatar(string $avatar = null, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'avatar' => $avatar ?: 'avatars/default-user.png'
        ]));
    }
    
    /**
     * Create a user with password change timestamp
     * 
     * @param string $passwordChangedAt Password change datetime
     * @param array $attributes Override attributes
     * @return int User ID
     */
    public static function createWithPasswordChange(string $passwordChangedAt = null, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'password_changed_at' => $passwordChangedAt ?: date('Y-m-d H:i:s', strtotime('-7 days'))
        ]));
    }
}