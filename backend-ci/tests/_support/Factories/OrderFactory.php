<?php

namespace Tests\Support\Factories;

/**
 * Factory for creating order test data
 * 
 * @agent-factory: Order entity factory
 * @agent-pattern: Factory pattern for order test data
 * @agent-reusable: HIGH
 */
class OrderFactory extends BaseFactory
{
    protected static string $table = 'orders';
    
    protected static array $defaultAttributes = [
        'code' => null, // Will be generated
        'order_number' => null, // Will be generated
        'customer_id' => null, // Will be generated if needed
        'customer_group_id' => null,
        'branch_id' => null,
        'warehouse_id' => null,
        'order_date' => null, // Will be set to today
        'order_type' => 'online',
        'pos_profile_id' => null,
        'pos_shift_id' => null,
        'tax_template_id' => null,
        'tax_total' => 0.00,
        'rounding_adjustment' => 0.00,
        'payment_method' => null,
        'status' => 'draft',
        'coupon_code' => null,
        'coupon_discount' => 0.00,
        'loyalty_points_redeemed' => 0,
        'loyalty_discount' => 0.00,
        'loyalty_points_earned' => 0,
        'subtotal' => 0.00,
        'discount_total' => 0.00,
        'shipping_fee' => 0.00,
        'total' => 0.00,
        'paid_amount' => 0.00,
        'debt_amount' => 0.00,
        'payment_status' => null,
        'is_paid' => 0,
        'applied_price_list_id' => null,
        'shipping_name' => null,
        'shipping_phone' => null,
        'shipping_address' => null,
        'shipping_ward' => null,
        'shipping_district' => null,
        'shipping_city' => null,
        'notes' => null,
        'confirmed_at' => null,
        'processing_at' => null,
        'shipping_at' => null,
        'delivered_at' => null,
        'completed_at' => null,
        'cancelled_at' => null,
        'cancellation_reason' => null,
        'cod_collected' => 0,
        'created_at' => null, // Will be set automatically
        'updated_at' => null, // Will be set automatically
        'deleted_at' => null,
    ];
    
    /**
     * Create an order with specific attributes
     * 
     * @param array $attributes Override attributes
     * @return int Order ID
     */
    public static function create(array $attributes = []): int
    {
        // Generate order code if not provided
        if (!isset($attributes['code'])) {
            $attributes['code'] = static::generateCode('ORD');
        }
        
        // Generate order number if not provided
        if (!isset($attributes['order_number'])) {
            $attributes['order_number'] = 'ORD-' . date('Ymd') . '-' . str_pad(mt_rand(1, 999), 3, '0', STR_PAD_LEFT);
        }
        
        // Generate order date if not provided
        if (!isset($attributes['order_date'])) {
            $attributes['order_date'] = date('Y-m-d');
        }
        
        // Generate customer if customer_id is provided as 'create'
        if (isset($attributes['customer_id']) && $attributes['customer_id'] === 'create') {
            $attributes['customer_id'] = CustomerFactory::create();
        }
        
        // Calculate total if subtotal is provided but total is not
        if (isset($attributes['subtotal']) && !isset($attributes['total'])) {
            $discountTotal = $attributes['discount_total'] ?? 0.00;
            $shippingFee = $attributes['shipping_fee'] ?? 0.00;
            $attributes['total'] = $attributes['subtotal'] - $discountTotal + $shippingFee;
        }
        
        return parent::create($attributes);
    }
    
    /**
     * Create multiple orders
     * 
     * @param int $count Number of orders to create
     * @param array $attributes Override attributes
     * @return array Array of order IDs
     */
    public static function createMany(int $count, array $attributes = []): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            // Generate unique data for each order
            $orderAttributes = array_merge($attributes, [
                'order_date' => isset($attributes['order_date']) ? $attributes['order_date'] : date('Y-m-d', strtotime("-$i days")),
            ]);
            
            // Create customer for each order if needed
            if (isset($orderAttributes['customer_id']) && $orderAttributes['customer_id'] === 'create') {
                $orderAttributes['customer_id'] = CustomerFactory::create();
            }
            
            $ids[] = static::create($orderAttributes);
        }
        return $ids;
    }
    
    /**
     * Create a pending order
     * 
     * @param array $attributes Override attributes
     * @return int Order ID
     */
    public static function createPending(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'status' => 'pending'
        ]));
    }
    
    /**
     * Create a completed order
     * 
     * @param array $attributes Override attributes
     * @return int Order ID
     */
    public static function createCompleted(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'status' => 'completed'
        ]));
    }
    
    /**
     * Create an order with items
     * 
     * @param array $items Array of item data
     * @param array $attributes Order attributes
     * @return int Order ID
     */
    public static function createWithItems(array $items, array $attributes = []): int
    {
        $orderId = static::create($attributes);
        
        $subtotal = 0.00;
        foreach ($items as $item) {
            // Create product if product_id is provided as 'create'
            if (isset($item['product_id']) && $item['product_id'] === 'create') {
                $item['product_id'] = ProductFactory::create();
            }
            
            // Set default values for item
            $itemData = array_merge([
                'order_id' => $orderId,
                'quantity' => 1,
                'base_price' => 100.00,
                'final_price' => 100.00,
            ], $item);
            
            // Insert order item
            $db = static::getDb();
            $db->table('order_items')->insert($itemData);
            
            $subtotal += $itemData['final_price'] * $itemData['quantity'];
        }
        
        // Update order totals
        $db = static::getDb();
        $db->table('orders')
           ->where('id', $orderId)
           ->update([
               'subtotal' => $subtotal,
               'total' => $subtotal - ($attributes['discount_total'] ?? 0.00),
               'updated_at' => date('Y-m-d H:i:s')
           ]);
        
        return $orderId;
    }
    
    /**
     * Create an order with specific amounts
     * 
     * @param float $subtotal Subtotal amount
     * @param float $discountTotal Discount amount
     * @param array $attributes Override attributes
     * @return int Order ID
     */
    public static function createWithAmounts(float $subtotal, float $discountTotal = 0.00, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'total' => $subtotal - $discountTotal
        ]));
    }
    
    /**
     * Create an order for a specific customer
     * 
     * @param int $customerId Customer ID
     * @param array $attributes Override attributes
     * @return int Order ID
     */
    public static function createForCustomer(int $customerId, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'customer_id' => $customerId
        ]));
    }
    
    /**
     * Create an order in a specific customer group
     * 
     * @param int $customerGroupId Customer group ID
     * @param array $attributes Override attributes
     * @return int Order ID
     */
    public static function createInCustomerGroup(int $customerGroupId, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'customer_group_id' => $customerGroupId
        ]));
    }
    
    /**
     * Create an order with a specific date
     * 
     * @param string $orderDate Order date (Y-m-d format)
     * @param array $attributes Override attributes
     * @return int Order ID
     */
    public static function createWithDate(string $orderDate, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'order_date' => $orderDate
        ]));
    }
    
    /**
     * Create an order with applied price list
     * 
     * @param int $priceListId Price list ID
     * @param string $priceListName Price list name
     * @param array $attributes Override attributes
     * @return int Order ID
     */
    public static function createWithPriceList(int $priceListId, string $priceListName = null, array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'applied_price_list_id' => $priceListId
        ]));
    }
    
    /**
     * Create a cancelled order
     * 
     * @param array $attributes Override attributes
     * @return int Order ID
     */
    public static function createCancelled(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'status' => 'cancelled'
        ]));
    }
    
    /**
     * Create a processing order
     * 
     * @param array $attributes Override attributes
     * @return int Order ID
     */
    public static function createProcessing(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'status' => 'processing'
        ]));
    }
    
    /**
     * Create a shipped order
     * 
     * @param array $attributes Override attributes
     * @return int Order ID
     */
    public static function createShipped(array $attributes = []): int
    {
        return static::create(array_merge($attributes, [
            'status' => 'shipped'
        ]));
    }
    
    /**
     * Create an order with random amounts
     * 
     * @param float $minAmount Minimum amount
     * @param float $maxAmount Maximum amount
     * @param array $attributes Override attributes
     * @return int Order ID
     */
    public static function createWithRandomAmounts(float $minAmount = 50.00, float $maxAmount = 1000.00, array $attributes = []): int
    {
        $subtotal = static::randomFloat($minAmount, $maxAmount);
        $discountTotal = static::randomFloat(0.00, $subtotal * 0.2); // Max 20% discount
        
        return static::createWithAmounts($subtotal, $discountTotal, $attributes);
    }
    
    /**
     * Create an order with items and random amounts
     * 
     * @param int $itemCount Number of items to create
     * @param array $attributes Order attributes
     * @return int Order ID
     */
    public static function createWithRandomItems(int $itemCount = 3, array $attributes = []): int
    {
        $items = [];
        $subtotal = 0.00;
        
        for ($i = 0; $i < $itemCount; $i++) {
            $quantity = mt_rand(1, 5);
            $basePrice = static::randomFloat(10.00, 200.00);
            $finalPrice = $basePrice; // Could apply discounts here
            
            $items[] = [
                'product_id' => 'create',
                'quantity' => $quantity,
                'base_price' => $basePrice,
                'final_price' => $finalPrice,
            ];
            
            $subtotal += $finalPrice * $quantity;
        }
        
        return static::createWithItems($items, array_merge($attributes, [
            'subtotal' => $subtotal,
            'total' => $subtotal
        ]));
    }
}