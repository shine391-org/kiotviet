<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Cash transactions schema model.
 * 
 * @agent-model: cash_transactions
 * @agent-pattern: Passive schema-only model
 * @agent-usage: Extend with service/repository layers
 */
class CashTransactionModel extends Model
{
    protected $table = 'cash_transactions';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';

    protected $allowedFields = [
        'type',
        'amount',
        'category',
        'payment_method',
        'status',
        'account_name',
        'bank_account',
        'description',
        'reference_type',
        'reference_id',
        'reference_code',
        'branch_id',
        'created_by',
        'created_by_name',
        'staff_name',
        'payer_code',
        'payer_name',
        'payer_phone',
        'payer_address',
        'transfer_note',
        'transaction_date',
        'note',
    ];

    // Transaction types
    public const TYPE_RECEIPT = 'RECEIPT';
    public const TYPE_PAYMENT = 'PAYMENT';

    // RECEIPT categories (Thu)
    public const CATEGORY_SALES = 'sales';
    public const CATEGORY_REFUND = 'refund';
    public const CATEGORY_DEPOSIT = 'deposit';
    public const CATEGORY_OTHER_INCOME = 'other_income';
    public const CATEGORY_SHIPPING_COD = 'shipping_cod';

    // PAYMENT categories (Chi)
    public const CATEGORY_PURCHASE = 'purchase';
    public const CATEGORY_SALARY = 'salary';
    public const CATEGORY_EXPENSE = 'expense';
    public const CATEGORY_WITHDRAWAL = 'withdrawal';
    public const CATEGORY_OTHER_EXPENSE = 'other_expense';
    public const CATEGORY_REFUND_PAYMENT = 'refund'; // hoàn tiền cho khách (chi)
    public const CATEGORY_SHIPPING_FEE = 'shipping_fee';

    // Reference types
    public const REFERENCE_ORDER = 'order';
    public const REFERENCE_ORDER_PAYMENT = 'order_payment';
    public const REFERENCE_PURCHASE_ORDER = 'purchase_order';
    public const REFERENCE_RETURN_ORDER = 'return_order';
    public const REFERENCE_SHIPPING_SETTLEMENT = 'shipping_settlement';
    public const REFERENCE_EXPENSE = 'expense';
    public const REFERENCE_MANUAL = 'manual';

    protected $validationRules = [
        'type' => 'required|in_list[RECEIPT,PAYMENT]',
        'amount' => 'required|decimal|greater_than[0]',
        'category' => 'required|max_length[50]',
        'branch_id' => 'required|is_natural_no_zero',
        'created_by' => 'required|is_natural_no_zero',
        'transaction_date' => 'required|valid_date',
    ];

    protected $validationMessages = [
        'type' => [
            'required' => 'Transaction type is required',
            'in_list' => 'Transaction type must be RECEIPT or PAYMENT',
        ],
        'amount' => [
            'required' => 'Amount is required',
            'decimal' => 'Amount must be a valid decimal number',
            'greater_than' => 'Amount must be greater than 0',
        ],
        'category' => [
            'required' => 'Category is required',
            'max_length' => 'Category is too long (max 50 characters)',
        ],
        'branch_id' => [
            'required' => 'Branch ID is required',
            'is_natural_no_zero' => 'Branch ID must be a valid positive number',
        ],
        'created_by' => [
            'required' => 'Created by user ID is required',
            'is_natural_no_zero' => 'User ID must be a valid positive number',
        ],
        'transaction_date' => [
            'required' => 'Transaction date is required',
            'valid_date' => 'Transaction date must be a valid date',
        ],
    ];

    /**
     * Get all allowed RECEIPT categories.
     * 
     * @return array
     */
    public static function getReceiptCategories(): array
    {
        return [
            self::CATEGORY_SALES,
            self::CATEGORY_REFUND,
            self::CATEGORY_DEPOSIT,
            self::CATEGORY_OTHER_INCOME,
            self::CATEGORY_SHIPPING_COD,
        ];
    }

    /**
     * Get all allowed PAYMENT categories.
     * 
     * @return array
     */
    public static function getPaymentCategories(): array
    {
        return [
            self::CATEGORY_PURCHASE,
            self::CATEGORY_SALARY,
            self::CATEGORY_EXPENSE,
            self::CATEGORY_WITHDRAWAL,
            self::CATEGORY_OTHER_EXPENSE,
            self::CATEGORY_REFUND_PAYMENT,
            self::CATEGORY_SHIPPING_FEE,
        ];
    }

    /**
     * Get all allowed categories.
     * 
     * @return array
     */
    public static function getAllCategories(): array
    {
        return array_merge(
            self::getReceiptCategories(),
            self::getPaymentCategories()
        );
    }

    /**
     * Get all allowed reference types.
     *
     * @return array
     */
    public static function getAllowedReferenceTypes(): array
    {
        return [
            self::REFERENCE_ORDER,
            self::REFERENCE_PURCHASE_ORDER,
            self::REFERENCE_ORDER_PAYMENT,
            self::REFERENCE_RETURN_ORDER,
            self::REFERENCE_SHIPPING_SETTLEMENT,
            self::REFERENCE_EXPENSE,
            self::REFERENCE_MANUAL,
        ];
    }

    /**
     * Check if category is valid for given type.
     * 
     * @param string $type
     * @param string $category
     * @return bool
     */
    public static function isValidCategory(string $type, string $category): bool
    {
        if ($type === self::TYPE_RECEIPT) {
            return in_array($category, self::getReceiptCategories(), true);
        }

        if ($type === self::TYPE_PAYMENT) {
            return in_array($category, self::getPaymentCategories(), true);
        }

        return false;
    }
}
