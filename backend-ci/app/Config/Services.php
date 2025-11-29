<?php

namespace Config;

use App\Repositories\Attributes\AttributeRepository;
use App\Repositories\Branches\BranchRepository;
use App\Repositories\ProductMedia\ProductMediaRepository;
use App\Repositories\Products\ProductRepository;
use App\Repositories\Products\ProductBatchRepository;
use App\Repositories\Products\ProductSerialNumberRepository;
use App\Repositories\PriceLists\PriceListItemRepository;
use App\Repositories\PriceLists\PriceListRepository;
use App\Repositories\PriceLists\CustomerPriceListRepository;
use App\Repositories\PriceLists\ProjectPriceListRepository;
use App\Repositories\Pricing\PricingRuleRepository;
use App\Repositories\Pricing\PriceHistoryRepository;
use App\Repositories\PaymentMethods\PaymentMethodRepository;
use App\Repositories\POS\POSProfileRepository;
use App\Repositories\POS\POSShiftPaymentRepository;
use App\Repositories\POS\POSShiftRepository;
use App\Repositories\POS\POSOfflineQueueRepository;
use App\Repositories\Payments\PaymentEntryRepository;
use App\Repositories\Taxes\TaxTemplateRepository;
use App\Repositories\Taxes\TaxChargeRepository;
use App\Repositories\Invoices\InvoiceRepository;
use App\Repositories\Returns\ReturnRepository;
use App\Repositories\DeliveryNotes\DeliveryNoteRepository;
use App\Repositories\DeliveryNotes\DeliveryNoteItemRepository;
use App\Repositories\Inventory\InventoryMovementRepository;
use App\Repositories\Customers\CustomerRepository;
use App\Repositories\OrderStatusLogs\OrderStatusLogRepository;
use App\Repositories\Orders\OrderRepository;
use App\Repositories\Orders\OrderPaymentRepository;
use App\Repositories\Webhooks\WebhookEventRepository;
use App\Repositories\Webhooks\WebhookSubscriptionRepository;
use App\Repositories\Approvals\ApprovalRuleRepository;
use App\Repositories\Approvals\ApprovalRepository;
use App\Repositories\Approvals\ApprovalActionRepository;
use App\Repositories\Inventory\StockLedgerRepository;
use App\Repositories\Inventory\StockBinRepository;
use App\Repositories\Inventory\StockReconciliationRepository;
use App\Repositories\Inventory\ReorderLevelRepository;
use App\Repositories\Inventory\PurchaseSuggestionRepository;
use App\Repositories\Orders\OrderTemplateRepository;
use App\Repositories\Orders\OrderSubscriptionRepository;
use App\Repositories\Manufacturing\BOMRepository;
use App\Repositories\Manufacturing\WorkOrderRepository;
use App\Repositories\Ecommerce\EcommerceWebhookLogRepository;
use App\Repositories\Quality\QualityInspectionRepository;
use App\Repositories\Quality\QualityParameterRepository;
use App\Services\Customers\CustomerService;
use App\Services\Products\ProductService;
use App\Services\Products\ProductImportService;
use App\Services\Products\ProductExportService;
use App\Services\Products\ProductBatchService;
use App\Services\Products\ProductSerialNumberService;
use App\Services\ProductMedia\ProductMediaService;
use App\Services\Attributes\AttributeService;
use App\Services\PriceLists\PriceCalculatorService;
use App\Services\PriceLists\PriceListService;
use App\Services\Pricing\PricingRuleService;
use App\Services\Pricing\PricingService;
use App\Services\PaymentMethods\PaymentMethodService;
use App\Services\POS\POSPaymentSplitService;
use App\Services\POS\POSProfileService;
use App\Services\POS\POSShiftService;
use App\Services\POS\POSOfflineService;
use App\Services\POS\POSIdempotencyService;
use App\Services\POS\POSTaxService;
use App\Services\Payments\PaymentEntryService;
use App\Services\Coupons\CouponService;
use App\Services\Loyalty\LoyaltyService;
use App\Services\Invoices\InvoiceService;
use App\Services\Invoices\VATCalculator;
use App\Services\Invoices\InvoicePDFGenerator;
use App\Services\Returns\ReturnService;
use App\Services\DeliveryNotes\DeliveryNoteService;
use App\Services\Inventory\StockLedgerService;
use App\Services\Inventory\StockReconciliationService;
use App\Services\Inventory\ReorderPlanningService;
use App\Services\Inventory\PurchaseSuggestionService;
use App\Services\Orders\OrderStatusService;
use App\Services\Orders\OrderStatusTransition;
use App\Services\Orders\OrderCancellationService;
use App\Services\Orders\OrderTemplateService;
use App\Services\Orders\OrderSubscriptionService;
use App\Services\Manufacturing\BOMService;
use App\Services\Manufacturing\WorkOrderService;
use App\Services\Ecommerce\EcommerceIntegrationService;
use App\Services\Subscriptions\SubscriptionService;
use App\Services\PurchaseOrders\PurchaseOrderStatusService;
use App\Services\Inventory\InventoryMovementLogger;
use App\Services\Orders\OrderService;
use App\Services\Webhooks\WebhookDispatcher;
use App\Services\Webhooks\WebhookSubscriptionService;
use App\Transformers\CustomerTransformer;
use App\Validators\ProductMediaDateValidator;
use App\Validators\ProductMediaSearchValidator;
use App\Validators\ProductMediaValidator;
use App\Validators\ProductValidator;
use App\Validators\ProductBatchValidator;
use App\Validators\ProductSerialValidator;
use App\Validators\PricingRuleValidator;
use App\Validators\CustomerPriceListValidator;
use App\Validators\ApprovalRuleValidator;
use App\Validators\ApprovalRequestValidator;
use App\Validators\DeliveryNoteValidator;
use App\Validators\StockLedgerValidator;
use App\Validators\StockReconciliationValidator;
use App\Validators\ReorderLevelValidator;
use App\Validators\PurchaseSuggestionValidator;
use App\Validators\OrderTemplateValidator;
use App\Validators\OrderSubscriptionValidator;
use App\Validators\BOMValidator;
use App\Validators\WorkOrderValidator;
use App\Validators\POSProfileValidator;
use App\Validators\POSShiftValidator;
use App\Validators\POSOfflineValidator;
use App\Validators\CouponValidator;
use App\Validators\LoyaltyProgramValidator;
use App\Validators\WebhookPayloadValidator;
use App\Validators\SubscriptionValidator;
use App\Validators\QualityInspectionValidator;
use App\Validators\QualityParameterValidator;
use App\Validators\PriceListValidator;
use App\Validators\PaymentMethodValidator;
use App\Validators\InvoiceValidator;
use App\Validators\ReturnValidator;
use App\Validators\OrderValidator;
use App\Validators\WebhookSubscriptionValidator;
use App\Validators\AttributeValidator;
use App\Validators\CustomerValidator;
use App\Repositories\Inventory\InventoryRepository;
use App\Services\Inventory\InventoryService;
use App\Validators\InventoryValidator;
use App\Services\Approvals\ApprovalRuleService;
use App\Services\Approvals\ApprovalService;
use App\Services\Approvals\ApprovalHook;
use App\Services\Quality\QualityInspectionService;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    public static function productRepository(bool $getShared = true): ProductRepository
    {
        if ($getShared) {
            return static::getSharedInstance('productRepository');
        }

        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ProductRepository(null, null, null, $db);
    }

    public static function productValidator(bool $getShared = true): ProductValidator
    {
        if ($getShared) {
            return static::getSharedInstance('productValidator');
        }

        return new ProductValidator();
    }

    public static function productService(bool $getShared = true): ProductService
    {
        if ($getShared) {
            return static::getSharedInstance('productService');
        }

        return new ProductService(
            static::productRepository(false),
            static::productValidator(false),
            static::priceCalculatorService(false)
        );
    }

    public static function pricingRuleValidator(bool $getShared = true): PricingRuleValidator
    {
        return $getShared ? static::getSharedInstance('pricingRuleValidator') : new PricingRuleValidator();
    }

    public static function customerPriceListValidator(bool $getShared = true): CustomerPriceListValidator
    {
        return $getShared ? static::getSharedInstance('customerPriceListValidator') : new CustomerPriceListValidator();
    }

    public static function pricingRuleRepository(bool $getShared = true): PricingRuleRepository
    {
        if ($getShared) {
            return static::getSharedInstance('pricingRuleRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new PricingRuleRepository(null, $db);
    }

    public static function priceHistoryRepository(bool $getShared = true): PriceHistoryRepository
    {
        return $getShared ? static::getSharedInstance('priceHistoryRepository') : new PriceHistoryRepository();
    }

    public static function customerPriceListRepository(bool $getShared = true): CustomerPriceListRepository
    {
        if ($getShared) {
            return static::getSharedInstance('customerPriceListRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new CustomerPriceListRepository(null, $db);
    }

    public static function projectPriceListRepository(bool $getShared = true): ProjectPriceListRepository
    {
        if ($getShared) {
            return static::getSharedInstance('projectPriceListRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ProjectPriceListRepository(null, $db);
    }

    public static function pricingRuleService(bool $getShared = true): PricingRuleService
    {
        if ($getShared) {
            return static::getSharedInstance('pricingRuleService');
        }
        return new PricingRuleService(
            static::pricingRuleRepository(false),
            static::pricingRuleValidator(false)
        );
    }

    public static function pricingService(bool $getShared = true): PricingService
    {
        if ($getShared) {
            return static::getSharedInstance('pricingService');
        }
        return new PricingService(
            static::priceCalculatorService(false),
            static::pricingRuleService(false),
            static::customerPriceListRepository(false),
            static::projectPriceListRepository(false),
            static::priceHistoryRepository(false)
        );
    }

    public static function productBatchRepository(bool $getShared = true): ProductBatchRepository
    {
        if ($getShared) {
            return static::getSharedInstance('productBatchRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ProductBatchRepository(null, $db);
    }

    public static function productBatchValidator(bool $getShared = true): ProductBatchValidator
    {
        if ($getShared) {
            return static::getSharedInstance('productBatchValidator');
        }
        return new ProductBatchValidator();
    }

    public static function productBatchService(bool $getShared = true): ProductBatchService
    {
        if ($getShared) {
            return static::getSharedInstance('productBatchService');
        }
        return new ProductBatchService(
            static::productBatchRepository(false),
            static::productBatchValidator(false),
            static::inventoryRepository(false),
            static::inventoryMovementLogger(false)
        );
    }

    public static function productSerialNumberRepository(bool $getShared = true): ProductSerialNumberRepository
    {
        if ($getShared) {
            return static::getSharedInstance('productSerialNumberRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ProductSerialNumberRepository(null, $db);
    }

    public static function productSerialValidator(bool $getShared = true): ProductSerialValidator
    {
        if ($getShared) {
            return static::getSharedInstance('productSerialValidator');
        }
        return new ProductSerialValidator();
    }

    public static function productSerialNumberService(bool $getShared = true): ProductSerialNumberService
    {
        if ($getShared) {
            return static::getSharedInstance('productSerialNumberService');
        }
        return new ProductSerialNumberService(
            static::productSerialNumberRepository(false),
            static::productSerialValidator(false),
            static::productBatchRepository(false)
        );
    }

    public static function approvalRuleValidator(bool $getShared = true): ApprovalRuleValidator
    {
        return $getShared ? static::getSharedInstance('approvalRuleValidator') : new ApprovalRuleValidator();
    }

    public static function approvalRequestValidator(bool $getShared = true): ApprovalRequestValidator
    {
        return $getShared ? static::getSharedInstance('approvalRequestValidator') : new ApprovalRequestValidator();
    }

    public static function approvalRuleRepository(bool $getShared = true): ApprovalRuleRepository
    {
        if ($getShared) {
            return static::getSharedInstance('approvalRuleRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ApprovalRuleRepository(null, $db);
    }

    public static function approvalRepository(bool $getShared = true): ApprovalRepository
    {
        if ($getShared) {
            return static::getSharedInstance('approvalRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        $repo = new ApprovalRepository(null, $db);
        $repo->db = $db;
        return $repo;
    }

    public static function approvalActionRepository(bool $getShared = true): ApprovalActionRepository
    {
        if ($getShared) {
            return static::getSharedInstance('approvalActionRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ApprovalActionRepository(null, $db);
    }

    public static function approvalRuleService(bool $getShared = true): ApprovalRuleService
    {
        if ($getShared) {
            return static::getSharedInstance('approvalRuleService');
        }
        return new ApprovalRuleService(
            static::approvalRuleRepository(false),
            static::approvalRuleValidator(false)
        );
    }

    public static function approvalService(bool $getShared = true): ApprovalService
    {
        if ($getShared) {
            return static::getSharedInstance('approvalService');
        }
        return new ApprovalService(
            static::approvalRepository(false),
            static::approvalActionRepository(false),
            static::approvalRuleService(false),
            static::approvalRequestValidator(false)
        );
    }

    public static function approvalHook(bool $getShared = true): ApprovalHook
    {
        if ($getShared) {
            return static::getSharedInstance('approvalHook');
        }
        return new ApprovalHook(static::approvalService(false));
    }

    public static function stockLedgerValidator(bool $getShared = true): StockLedgerValidator
    {
        return $getShared ? static::getSharedInstance('stockLedgerValidator') : new StockLedgerValidator();
    }

    public static function stockReconciliationValidator(bool $getShared = true): StockReconciliationValidator
    {
        return $getShared ? static::getSharedInstance('stockReconciliationValidator') : new StockReconciliationValidator();
    }

    public static function stockLedgerRepository(bool $getShared = true): StockLedgerRepository
    {
        if ($getShared) {
            return static::getSharedInstance('stockLedgerRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new StockLedgerRepository(null, $db);
    }

    public static function stockBinRepository(bool $getShared = true): StockBinRepository
    {
        if ($getShared) {
            return static::getSharedInstance('stockBinRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new StockBinRepository(null, $db);
    }

    public static function stockReconciliationRepository(bool $getShared = true): StockReconciliationRepository
    {
        if ($getShared) {
            return static::getSharedInstance('stockReconciliationRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new StockReconciliationRepository(null, null, $db);
    }

    public static function stockLedgerService(bool $getShared = true): StockLedgerService
    {
        if ($getShared) {
            return static::getSharedInstance('stockLedgerService');
        }
        return new StockLedgerService(
            static::stockLedgerRepository(false),
            static::stockBinRepository(false),
            static::stockLedgerValidator(false)
        );
    }

    public static function stockReconciliationService(bool $getShared = true): StockReconciliationService
    {
        if ($getShared) {
            return static::getSharedInstance('stockReconciliationService');
        }
        return new StockReconciliationService(
            static::stockReconciliationRepository(false),
            static::stockReconciliationValidator(false),
            static::stockLedgerService(false),
            static::stockBinRepository(false)
        );
    }

    public static function deliveryNoteValidator(bool $getShared = true): DeliveryNoteValidator
    {
        return $getShared ? static::getSharedInstance('deliveryNoteValidator') : new DeliveryNoteValidator();
    }

    public static function deliveryNoteRepository(bool $getShared = true): DeliveryNoteRepository
    {
        if ($getShared) {
            return static::getSharedInstance('deliveryNoteRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new DeliveryNoteRepository(null, null, $db);
    }

    public static function deliveryNoteItemRepository(bool $getShared = true): DeliveryNoteItemRepository
    {
        if ($getShared) {
            return static::getSharedInstance('deliveryNoteItemRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new DeliveryNoteItemRepository(null, $db);
    }

    public static function deliveryNoteService(bool $getShared = true): DeliveryNoteService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('deliveryNoteService');
        }
        return new DeliveryNoteService(
            static::deliveryNoteRepository(false),
            static::deliveryNoteItemRepository(false),
            static::deliveryNoteValidator(false),
            static::orderRepository(false),
            static::inventoryRepository(false),
            static::inventoryMovementLogger(false),
            static::productBatchService(false),
            static::productSerialNumberService(false)
        );
    }

    public static function productImportService(bool $getShared = true): ProductImportService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('productImportService');
        }

        return new ProductImportService(
            static::productService(false),
            static::productValidator(false)
        );
    }

    public static function productExportService(bool $getShared = true): ProductExportService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('productExportService');
        }

        return new ProductExportService(
            static::productService(false)
        );
    }

    public static function productVariantRepository(bool $getShared = true): \App\Repositories\ProductVariants\ProductVariantRepository
    {
        if ($getShared) {
            return static::getSharedInstance('productVariantRepository');
        }

        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new \App\Repositories\ProductVariants\ProductVariantRepository(null, null, $db);
    }

    public static function productVariantValidator(bool $getShared = true): \App\Validators\ProductVariantValidator
    {
        if ($getShared) {
            return static::getSharedInstance('productVariantValidator');
        }

        return new \App\Validators\ProductVariantValidator();
    }

    public static function productVariantService(bool $getShared = true): \App\Services\ProductVariants\ProductVariantService
    {
        if ($getShared) {
            return static::getSharedInstance('productVariantService');
        }

        return new \App\Services\ProductVariants\ProductVariantService(
            static::productVariantRepository(false),
            static::productVariantValidator(false),
            static::productRepository(false)
        );
    }

    public static function attributeRepository(bool $getShared = true): AttributeRepository
    {
        if ($getShared) {
            return static::getSharedInstance('attributeRepository');
        }

        return new AttributeRepository();
    }

    public static function attributeValidator(bool $getShared = true): AttributeValidator
    {
        if ($getShared) {
            return static::getSharedInstance('attributeValidator');
        }

        return new AttributeValidator();
    }

    public static function attributeService(bool $getShared = true): AttributeService
    {
        if ($getShared) {
            return static::getSharedInstance('attributeService');
        }

        return new AttributeService(
            static::attributeRepository(false),
            static::attributeValidator(false)
        );
    }

    public static function productMediaRepository(bool $getShared = true): ProductMediaRepository
    {
        if ($getShared) {
            return static::getSharedInstance('productMediaRepository');
        }

        return new ProductMediaRepository();
    }

    public static function productMediaValidator(bool $getShared = true): ProductMediaValidator
    {
        if ($getShared) {
            return static::getSharedInstance('productMediaValidator');
        }

        return new ProductMediaValidator();
    }

    public static function productMediaDateValidator(bool $getShared = true): ProductMediaDateValidator
    {
        if ($getShared) {
            return static::getSharedInstance('productMediaDateValidator');
        }

        return new ProductMediaDateValidator();
    }

    public static function productMediaSearchValidator(bool $getShared = true): ProductMediaSearchValidator
    {
        if ($getShared) {
            return static::getSharedInstance('productMediaSearchValidator');
        }

        return new ProductMediaSearchValidator();
    }

    public static function productMediaService(bool $getShared = true): ProductMediaService
    {
        if ($getShared) {
            return static::getSharedInstance('productMediaService');
        }

        return new ProductMediaService(
            static::productMediaRepository(false),
            static::productMediaValidator(false),
            static::productMediaDateValidator(false),
            static::productMediaSearchValidator(false)
        );
    }

    public static function inventoryRepository(bool $getShared = true): InventoryRepository
    {
        if ($getShared) {
            return static::getSharedInstance('inventoryRepository');
        }

        return new InventoryRepository();
    }

    public static function inventoryValidator(bool $getShared = true): InventoryValidator
    {
        if ($getShared) {
            return static::getSharedInstance('inventoryValidator');
        }

        return new InventoryValidator();
    }

    public static function inventoryService(bool $getShared = true): InventoryService
    {
        if ($getShared) {
            return static::getSharedInstance('inventoryService');
        }

        return new InventoryService(
            static::inventoryRepository(false),
            static::inventoryValidator(false)
        );
    }

    public static function reorderLevelValidator(bool $getShared = true): ReorderLevelValidator
    {
        return $getShared ? static::getSharedInstance('reorderLevelValidator') : new ReorderLevelValidator();
    }

    public static function purchaseSuggestionValidator(bool $getShared = true): PurchaseSuggestionValidator
    {
        return $getShared ? static::getSharedInstance('purchaseSuggestionValidator') : new PurchaseSuggestionValidator();
    }

    public static function reorderLevelRepository(bool $getShared = true): ReorderLevelRepository
    {
        if ($getShared) {
            return static::getSharedInstance('reorderLevelRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ReorderLevelRepository(null, $db);
    }

    public static function purchaseSuggestionRepository(bool $getShared = true): PurchaseSuggestionRepository
    {
        if ($getShared) {
            return static::getSharedInstance('purchaseSuggestionRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new PurchaseSuggestionRepository(null, $db);
    }

    public static function reorderPlanningService(bool $getShared = true): ReorderPlanningService
    {
        if ($getShared) {
            return static::getSharedInstance('reorderPlanningService');
        }

        return new ReorderPlanningService(
            static::reorderLevelRepository(false),
            static::purchaseSuggestionRepository(false),
            static::stockBinRepository(false),
            static::reorderLevelValidator(false),
            static::purchaseSuggestionValidator(false)
        );
    }

    public static function purchaseSuggestionService(bool $getShared = true): PurchaseSuggestionService
    {
        if ($getShared) {
            return static::getSharedInstance('purchaseSuggestionService');
        }

        return new PurchaseSuggestionService(
            static::purchaseSuggestionRepository(false),
            static::purchaseSuggestionValidator(false)
        );
    }

    public static function customerRepository(bool $getShared = true): CustomerRepository
    {
        if ($getShared) {
            return static::getSharedInstance('customerRepository');
        }

        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new CustomerRepository(null, $db);
    }

    public static function customerValidator(bool $getShared = true): CustomerValidator
    {
        return $getShared ? static::getSharedInstance('customerValidator') : new CustomerValidator();
    }

    public static function customerTransformer(bool $getShared = true): CustomerTransformer
    {
        return $getShared ? static::getSharedInstance('customerTransformer') : new CustomerTransformer();
    }

    public static function customerService(bool $getShared = true): CustomerService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('customerService');
        }

        return new CustomerService(
            static::customerRepository(false),
            static::customerValidator(false),
            static::customerTransformer(false)
        );
    }

    public static function priceListRepository(bool $getShared = true): PriceListRepository
    {
        if ($getShared) { return static::getSharedInstance('priceListRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new PriceListRepository(null, $db);
    }

    public static function priceListItemRepository(bool $getShared = true): PriceListItemRepository
    {
        if ($getShared) { return static::getSharedInstance('priceListItemRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new PriceListItemRepository(null, $db);
    }

    public static function priceListValidator(bool $getShared = true): PriceListValidator
    {
        return $getShared ? static::getSharedInstance('priceListValidator') : new PriceListValidator();
    }

    public static function priceListService(bool $getShared = true): PriceListService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('priceListService'); }

        return new PriceListService(
            static::priceListRepository(false),
            static::priceListItemRepository(false),
            static::priceListValidator(false),
            null,
            static::productRepository(false)
        );
    }

    public static function priceCalculatorService(bool $getShared = true): PriceCalculatorService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('priceCalculatorService'); }

        return new PriceCalculatorService(
            static::priceListRepository(false),
            static::priceListItemRepository(false),
            static::productRepository(false),
            static::productVariantRepository(false)
        );
    }

    public static function orderRepository(bool $getShared = true): OrderRepository
    {
        if ($getShared) { return static::getSharedInstance('orderRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new OrderRepository(null, null, $db);
    }

    public static function orderValidator(bool $getShared = true): OrderValidator
    {
        return $getShared ? static::getSharedInstance('orderValidator') : new OrderValidator();
    }

    public static function orderService(bool $getShared = true): OrderService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('orderService'); }

        return new OrderService(
            static::orderRepository(false),
            static::orderValidator(false),
            static::priceCalculatorService(false),
            static::pricingService(false),
            null,
            null,
            static::webhookDispatcher(false),
            null,
            static::inventoryMovementLogger(false),
            static::inventoryRepository(false),
            static::productBatchService(false),
            static::productSerialNumberService(false),
            static::posProfileService(false),
            static::posPaymentSplitService(false),
            static::posShiftService(false),
            static::couponService(false),
            static::loyaltyService(false),
            static::paymentEntryService(false),
            static::posTaxService(false)
        );
    }

    public static function orderTemplateValidator(bool $getShared = true): OrderTemplateValidator
    {
        return $getShared ? static::getSharedInstance('orderTemplateValidator') : new OrderTemplateValidator();
    }

    public static function orderSubscriptionValidator(bool $getShared = true): OrderSubscriptionValidator
    {
        return $getShared ? static::getSharedInstance('orderSubscriptionValidator') : new OrderSubscriptionValidator();
    }

    public static function orderTemplateRepository(bool $getShared = true): OrderTemplateRepository
    {
        if ($getShared) { return static::getSharedInstance('orderTemplateRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new OrderTemplateRepository(null, null, $db);
    }

    public static function orderSubscriptionRepository(bool $getShared = true): OrderSubscriptionRepository
    {
        if ($getShared) { return static::getSharedInstance('orderSubscriptionRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new OrderSubscriptionRepository(null, $db);
    }

    public static function orderTemplateService(bool $getShared = true): OrderTemplateService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('orderTemplateService'); }
        return new OrderTemplateService(
            static::orderTemplateRepository(false),
            static::orderTemplateValidator(false),
            static::orderService(false)
        );
    }

    public static function orderSubscriptionService(bool $getShared = true): OrderSubscriptionService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('orderSubscriptionService'); }
        return new OrderSubscriptionService(
            static::orderSubscriptionRepository(false),
            static::orderSubscriptionValidator(false),
            static::orderTemplateService(false)
        );
    }

    public static function bomValidator(bool $getShared = true): BOMValidator
    {
        return $getShared ? static::getSharedInstance('bomValidator') : new BOMValidator();
    }

    public static function workOrderValidator(bool $getShared = true): WorkOrderValidator
    {
        return $getShared ? static::getSharedInstance('workOrderValidator') : new WorkOrderValidator();
    }

    public static function bomRepository(bool $getShared = true): BOMRepository
    {
        if ($getShared) { return static::getSharedInstance('bomRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new BOMRepository(null, null, $db);
    }

    public static function workOrderRepository(bool $getShared = true): WorkOrderRepository
    {
        if ($getShared) { return static::getSharedInstance('workOrderRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new WorkOrderRepository(null, null, $db);
    }

    public static function bomService(bool $getShared = true): BOMService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('bomService'); }
        return new BOMService(
            static::bomRepository(false),
            static::bomValidator(false)
        );
    }

    public static function workOrderService(bool $getShared = true): WorkOrderService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('workOrderService'); }
        return new WorkOrderService(
            static::workOrderRepository(false),
            static::bomRepository(false),
            static::stockLedgerService(false),
            static::workOrderValidator(false)
        );
    }

    public static function subscriptionValidator(bool $getShared = true): SubscriptionValidator
    {
        return $getShared ? static::getSharedInstance('subscriptionValidator') : new SubscriptionValidator();
    }

    public static function subscriptionRepository(bool $getShared = true): \App\Repositories\Subscriptions\SubscriptionRepository
    {
        if ($getShared) { return static::getSharedInstance('subscriptionRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new \App\Repositories\Subscriptions\SubscriptionRepository(null, $db);
    }

    public static function subscriptionCycleRepository(bool $getShared = true): \App\Repositories\Subscriptions\SubscriptionCycleRepository
    {
        if ($getShared) { return static::getSharedInstance('subscriptionCycleRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new \App\Repositories\Subscriptions\SubscriptionCycleRepository(null, $db);
    }

    public static function subscriptionService(bool $getShared = true): SubscriptionService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('subscriptionService'); }
        return new SubscriptionService(
            static::subscriptionRepository(false),
            static::subscriptionCycleRepository(false),
            static::orderService(false),
            static::subscriptionValidator(false)
        );
    }

    public static function webhookPayloadValidator(bool $getShared = true): WebhookPayloadValidator
    {
        return $getShared ? static::getSharedInstance('webhookPayloadValidator') : new WebhookPayloadValidator();
    }

    public static function ecommerceWebhookLogRepository(bool $getShared = true): EcommerceWebhookLogRepository
    {
        if ($getShared) { return static::getSharedInstance('ecommerceWebhookLogRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new EcommerceWebhookLogRepository(null, $db);
    }

    public static function ecommerceIntegrationService(bool $getShared = true): EcommerceIntegrationService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('ecommerceIntegrationService'); }
        return new EcommerceIntegrationService(
            static::productRepository(false),
            static::orderService(false),
            static::ecommerceWebhookLogRepository(false),
            static::webhookPayloadValidator(false)
        );
    }

    public static function orderQueryService(bool $getShared = true): \App\Services\Orders\OrderQueryService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('orderQueryService'); }

        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new \App\Services\Orders\OrderQueryService(
            new \App\Repositories\Orders\OrderQueryRepository($db),
            new \App\Validators\OrderListValidator()
        );
    }

    public static function paymentMethodRepository(bool $getShared = true): PaymentMethodRepository
    {
        if ($getShared) { return static::getSharedInstance('paymentMethodRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new PaymentMethodRepository(null, $db);
    }

    public static function paymentMethodValidator(bool $getShared = true): PaymentMethodValidator
    {
        return $getShared ? static::getSharedInstance('paymentMethodValidator') : new PaymentMethodValidator();
    }

    public static function paymentMethodService(bool $getShared = true): PaymentMethodService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('paymentMethodService'); }

        return new PaymentMethodService(
            static::paymentMethodRepository(false),
            static::paymentMethodValidator(false)
        );
    }

    public static function posProfileRepository(bool $getShared = true): POSProfileRepository
    {
        if ($getShared) { return static::getSharedInstance('posProfileRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new POSProfileRepository(null, null, $db);
    }

    public static function posShiftPaymentRepository(bool $getShared = true): POSShiftPaymentRepository
    {
        if ($getShared) { return static::getSharedInstance('posShiftPaymentRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new POSShiftPaymentRepository(null, $db);
    }

    public static function posShiftRepository(bool $getShared = true): POSShiftRepository
    {
        if ($getShared) { return static::getSharedInstance('posShiftRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new POSShiftRepository(null, null, static::posShiftPaymentRepository(false), $db);
    }

    public static function posProfileValidator(bool $getShared = true): POSProfileValidator
    {
        return $getShared ? static::getSharedInstance('posProfileValidator') : new POSProfileValidator();
    }

    public static function posShiftValidator(bool $getShared = true): POSShiftValidator
    {
        return $getShared ? static::getSharedInstance('posShiftValidator') : new POSShiftValidator();
    }

    public static function posProfileService(bool $getShared = true): POSProfileService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('posProfileService'); }

        return new POSProfileService(
            static::posProfileRepository(false),
            static::posProfileValidator(false),
            static::paymentMethodRepository(false)
        );
    }

    public static function posShiftService(bool $getShared = true): POSShiftService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('posShiftService'); }
        return new POSShiftService(
            static::posShiftRepository(false),
            static::posShiftValidator(false)
        );
    }

    public static function posPaymentSplitService(bool $getShared = true): POSPaymentSplitService
    {
        return $getShared ? static::getSharedInstance('posPaymentSplitService') : new POSPaymentSplitService();
    }

    public static function posOfflineQueueRepository(bool $getShared = true): POSOfflineQueueRepository
    {
        if ($getShared) { return static::getSharedInstance('posOfflineQueueRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new POSOfflineQueueRepository(null, static::posIdempotencyService(false), $db);
    }

    public static function posIdempotencyService(bool $getShared = true): POSIdempotencyService
    {
        return $getShared ? static::getSharedInstance('posIdempotencyService') : new POSIdempotencyService();
    }

    public static function posOfflineValidator(bool $getShared = true): POSOfflineValidator
    {
        return $getShared ? static::getSharedInstance('posOfflineValidator') : new POSOfflineValidator();
    }

    public static function posOfflineService(bool $getShared = true): POSOfflineService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('posOfflineService'); }
        return new POSOfflineService(
            static::posOfflineQueueRepository(false),
            static::posOfflineValidator(false),
            static::orderService(false)
        );
    }

    public static function paymentEntryRepository(bool $getShared = true): PaymentEntryRepository
    {
        if ($getShared) { return static::getSharedInstance('paymentEntryRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new PaymentEntryRepository(null, $db);
    }

    public static function paymentEntryValidator(bool $getShared = true): \App\Validators\PaymentEntryValidator
    {
        return $getShared ? static::getSharedInstance('paymentEntryValidator') : new \App\Validators\PaymentEntryValidator();
    }

    public static function paymentEntryService(bool $getShared = true): PaymentEntryService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('paymentEntryService'); }
        return new PaymentEntryService(
            static::paymentEntryRepository(false),
            static::paymentEntryValidator(false)
        );
    }

    public static function taxTemplateRepository(bool $getShared = true): TaxTemplateRepository
    {
        if ($getShared) { return static::getSharedInstance('taxTemplateRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new TaxTemplateRepository(null, $db);
    }

    public static function taxChargeRepository(bool $getShared = true): TaxChargeRepository
    {
        if ($getShared) { return static::getSharedInstance('taxChargeRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new TaxChargeRepository(null, $db);
    }

    public static function taxTemplateValidator(bool $getShared = true): TaxTemplateValidator
    {
        return $getShared ? static::getSharedInstance('taxTemplateValidator') : new TaxTemplateValidator();
    }

    public static function posTaxService(bool $getShared = true): POSTaxService
    {
        return $getShared ? static::getSharedInstance('posTaxService') : new POSTaxService(
            static::taxTemplateRepository(false),
            static::taxChargeRepository(false)
        );
    }

    public static function couponRepository(bool $getShared = true): \App\Repositories\Coupons\CouponRepository
    {
        if ($getShared) { return static::getSharedInstance('couponRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new \App\Repositories\Coupons\CouponRepository(null, null, $db);
    }

    public static function couponValidator(bool $getShared = true): CouponValidator
    {
        return $getShared ? static::getSharedInstance('couponValidator') : new CouponValidator();
    }

    public static function couponService(bool $getShared = true): CouponService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('couponService'); }
        return new CouponService(
            static::couponRepository(false),
            static::couponValidator(false)
        );
    }

    public static function loyaltyRepository(bool $getShared = true): \App\Repositories\Loyalty\LoyaltyRepository
    {
        if ($getShared) { return static::getSharedInstance('loyaltyRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new \App\Repositories\Loyalty\LoyaltyRepository(null, null, null, $db);
    }

    public static function loyaltyProgramValidator(bool $getShared = true): LoyaltyProgramValidator
    {
        return $getShared ? static::getSharedInstance('loyaltyProgramValidator') : new LoyaltyProgramValidator();
    }

    public static function loyaltyService(bool $getShared = true): \App\Services\Loyalty\LoyaltyService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('loyaltyService'); }
        return new \App\Services\Loyalty\LoyaltyService(static::loyaltyRepository(false));
    }

    public static function invoiceRepository(bool $getShared = true): InvoiceRepository
    {
        if ($getShared) { return static::getSharedInstance('invoiceRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new InvoiceRepository(null, null, $db);
    }

    public static function invoiceValidator(bool $getShared = true): InvoiceValidator
    {
        return $getShared ? static::getSharedInstance('invoiceValidator') : new InvoiceValidator();
    }

    public static function vatCalculator(bool $getShared = true): VATCalculator
    {
        return $getShared ? static::getSharedInstance('vatCalculator') : new VATCalculator();
    }

    public static function invoicePdfGenerator(bool $getShared = true): InvoicePDFGenerator
    {
        return $getShared ? static::getSharedInstance('invoicePdfGenerator') : new InvoicePDFGenerator();
    }

    public static function invoiceService(bool $getShared = true): InvoiceService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('invoiceService'); }

        return new InvoiceService(
            static::invoiceRepository(false),
            static::invoiceValidator(false),
            null,
            static::vatCalculator(false),
            static::invoicePdfGenerator(false),
            static::webhookDispatcher(false)
        );
    }

    public static function returnRepository(bool $getShared = true): ReturnRepository
    {
        if ($getShared) { return static::getSharedInstance('returnRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ReturnRepository(null, null, $db);
    }

    public static function returnValidator(bool $getShared = true): ReturnValidator
    {
        return $getShared ? static::getSharedInstance('returnValidator') : new ReturnValidator();
    }

    public static function returnService(bool $getShared = true): ReturnService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('returnService'); }

        return new ReturnService(
            static::returnRepository(false),
            static::returnValidator(false),
            null,
            static::inventoryMovementLogger(false),
            static::webhookDispatcher(false),
            static::inventoryRepository(false)
        );
    }

    public static function orderStatusTransition(bool $getShared = true): \App\Services\Orders\OrderStatusTransition
    {
        return $getShared ? static::getSharedInstance('orderStatusTransition') : new \App\Services\Orders\OrderStatusTransition();
    }

    public static function orderStatusService(bool $getShared = true): \App\Services\Orders\OrderStatusService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('orderStatusService'); }

        return new \App\Services\Orders\OrderStatusService(
            static::orderRepository(false),
            static::orderStatusTransition(false),
            static::orderStatusLogRepository(false),
            static::orderPaymentRepository(false),
            static::inventoryRepository(false),
            static::inventoryMovementLogger(false),
            static::productBatchService(false),
            static::productSerialNumberService(false),
            static::approvalHook(false),
            static::webhookDispatcher(false)
        );
    }

    public static function orderPaymentRepository(bool $getShared = true): OrderPaymentRepository
    {
        if ($getShared) { return static::getSharedInstance('orderPaymentRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new OrderPaymentRepository($db);
    }

    public static function orderCancellationService(bool $getShared = true): \App\Services\Orders\OrderCancellationService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('orderCancellationService'); }
        return new \App\Services\Orders\OrderCancellationService(
            static::orderStatusService(false),
            null
        );
    }

    public static function purchaseOrderStatusService(bool $getShared = true): \App\Services\PurchaseOrders\PurchaseOrderStatusService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('purchaseOrderStatusService'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new \App\Services\PurchaseOrders\PurchaseOrderStatusService($db);
    }

    public static function branchRepository(bool $getShared = true): BranchRepository
    {
        if ($getShared) { return static::getSharedInstance('branchRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new BranchRepository(null, $db);
    }

    public static function orderStatusLogRepository(bool $getShared = true): OrderStatusLogRepository
    {
        if ($getShared) { return static::getSharedInstance('orderStatusLogRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new OrderStatusLogRepository(null, $db);
    }

    public static function inventoryMovementRepository(bool $getShared = true): InventoryMovementRepository
    {
        if ($getShared) { return static::getSharedInstance('inventoryMovementRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new InventoryMovementRepository(null, $db);
    }

    public static function inventoryMovementLogger(bool $getShared = true): InventoryMovementLogger
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('inventoryMovementLogger'); }
        return new InventoryMovementLogger(static::inventoryMovementRepository(false));
    }

    public static function qualityParameterValidator(bool $getShared = true): QualityParameterValidator
    {
        return $getShared ? static::getSharedInstance('qualityParameterValidator') : new QualityParameterValidator();
    }

    public static function qualityInspectionValidator(bool $getShared = true): QualityInspectionValidator
    {
        return $getShared ? static::getSharedInstance('qualityInspectionValidator') : new QualityInspectionValidator();
    }

    public static function qualityParameterRepository(bool $getShared = true): QualityParameterRepository
    {
        if ($getShared) { return static::getSharedInstance('qualityParameterRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new QualityParameterRepository(null, $db);
    }

    public static function qualityInspectionRepository(bool $getShared = true): QualityInspectionRepository
    {
        if ($getShared) { return static::getSharedInstance('qualityInspectionRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new QualityInspectionRepository(null, null, $db);
    }

    public static function qualityInspectionService(bool $getShared = true): QualityInspectionService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('qualityInspectionService'); }
        $inspectionRepo = static::qualityInspectionRepository(false);
        $parameterRepo = new QualityParameterRepository(null, $inspectionRepo->db());
        return new QualityInspectionService(
            $inspectionRepo,
            $parameterRepo,
            static::qualityInspectionValidator(false),
            static::qualityParameterValidator(false)
        );
    }

    public static function webhookSubscriptionRepository(bool $getShared = true): WebhookSubscriptionRepository
    {
        if ($getShared) { return static::getSharedInstance('webhookSubscriptionRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new WebhookSubscriptionRepository(null, $db);
    }

    public static function webhookEventRepository(bool $getShared = true): WebhookEventRepository
    {
        if ($getShared) { return static::getSharedInstance('webhookEventRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new WebhookEventRepository(null, $db);
    }

    public static function webhookSubscriptionValidator(bool $getShared = true): WebhookSubscriptionValidator
    {
        return $getShared ? static::getSharedInstance('webhookSubscriptionValidator') : new WebhookSubscriptionValidator();
    }

    public static function webhookSubscriptionService(bool $getShared = true): WebhookSubscriptionService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('webhookSubscriptionService'); }
        return new WebhookSubscriptionService(
            static::webhookSubscriptionRepository(false),
            static::webhookSubscriptionValidator(false)
        );
    }

    public static function webhookDispatcher(bool $getShared = true): WebhookDispatcher
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('webhookDispatcher'); }
        return new WebhookDispatcher(
            static::webhookSubscriptionRepository(false),
            static::webhookEventRepository(false)
        );
    }
}
