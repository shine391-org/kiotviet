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
use App\Repositories\PaymentMethods\PaymentMethodRepository;
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
use App\Services\PaymentMethods\PaymentMethodService;
use App\Services\Invoices\InvoiceService;
use App\Services\Invoices\VATCalculator;
use App\Services\Invoices\InvoicePDFGenerator;
use App\Services\Returns\ReturnService;
use App\Services\DeliveryNotes\DeliveryNoteService;
use App\Services\Orders\OrderStatusService;
use App\Services\Orders\OrderStatusTransition;
use App\Services\Orders\OrderCancellationService;
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
use App\Validators\DeliveryNoteValidator;
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
            null,
            null,
            static::webhookDispatcher(false),
            null,
            static::inventoryMovementLogger(false),
            static::inventoryRepository(false),
            static::productBatchService(false),
            static::productSerialNumberService(false)
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
