<?php

namespace Config;

use App\Repositories\Attributes\AttributeRepository;
use App\Repositories\Branches\BranchRepository;
use App\Repositories\Companies\CompanyRepository;
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
use App\Repositories\CRM\LeadRepository;
use App\Repositories\CRM\OpportunityRepository;
use App\Repositories\CRM\QuotationRepository;
use App\Repositories\Contracts\ContractRepository;
use App\Repositories\Contracts\ContractTemplateRepository;
use App\Repositories\Appointments\AppointmentRepository;
use App\Repositories\PurchaseOrders\PurchaseOrderRepository;
use App\Repositories\Inventory\GoodsReceiptRepository;
use App\Repositories\Accounting\LandedCostRepository;
use App\Repositories\Manufacturing\SubcontractingRepository;
use App\Repositories\Accounting\COARepository;
use App\Repositories\Accounting\GLEntryRepository;
use App\Repositories\Accounting\PurchaseInvoiceRepository;
use App\Repositories\Accounting\SalesInvoiceRepository;
use App\Repositories\Accounting\PaymentEntryRepository as AccountingPaymentEntryRepository;
use App\Repositories\Accounting\BankStatementRepository;
use App\Repositories\Accounting\BankReconciliationRepository;
use App\Repositories\Accounting\WithholdingRuleRepository;
use App\Repositories\Accounting\CreditLimitRepository;
use App\Repositories\Accounting\ExchangeRateRepository;
use App\Repositories\Taxes\TaxTemplateItemRepository;
use App\Repositories\Support\SupportTicketRepository;
use App\Repositories\Support\CommunicationRepository;
use App\Repositories\Support\TicketEventRepository;
use App\Repositories\Campaigns\CampaignRepository;
use App\Repositories\Campaigns\EmailCampaignRepository;
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
use App\Repositories\Permissions\ShareRepository;
use App\Repositories\Audits\AuditLogRepository;
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
use App\Services\Audits\AuditService;
use App\Services\Companies\CompanyService;
use App\Services\Permissions\DocumentAccessService;
use App\Services\Permissions\PermissionService;
use App\Services\Permissions\SharingService;
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
use App\Services\CRM\LeadService;
use App\Services\CRM\OpportunityService;
use App\Services\CRM\QuotationService;
use App\Services\CRM\QuotationNumberGenerator;
use App\Services\Contracts\ContractService;
use App\Services\Appointments\AppointmentService;
use App\Services\PurchaseOrders\PurchaseOrderService;
use App\Services\Inventory\GoodsReceiptService;
use App\Services\Accounting\LandedCostService;
use App\Services\Manufacturing\SubcontractingService;
use App\Services\Accounting\COAService;
use App\Services\Accounting\AccountingService;
use App\Services\Accounting\PurchaseInvoiceService;
use App\Services\Accounting\SalesInvoiceService;
use App\Services\Accounting\PaymentEntryService as AccountingPaymentEntryService;
use App\Services\Accounting\BankReconciliationService;
use App\Services\Accounting\WithholdingService;
use App\Services\Accounting\CreditControlService;
use App\Services\Accounting\CurrencyService;
use App\Services\Accounting\AgingService;
use App\Services\Taxes\TaxTemplateService;
use App\Services\Support\SupportTicketService;
use App\Services\Support\CommunicationService;
use App\Services\Campaigns\CampaignService;
use App\Services\Campaigns\EmailCampaignService;
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
use App\Validators\CompanyValidator;
use App\Validators\PermissionValidator;
use App\Validators\ShareValidator;
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
use App\Validators\LeadValidator;
use App\Validators\OpportunityValidator;
use App\Validators\QuotationValidator;
use App\Validators\ContractValidator;
use App\Validators\AppointmentValidator;
use App\Validators\COAValidator;
use App\Validators\GLEntryValidator;
use App\Validators\PurchaseInvoiceValidator;
use App\Validators\SalesInvoiceValidator;
use App\Validators\BankReconciliationValidator;
use App\Validators\WithholdingValidator;
use App\Validators\CreditControlValidator;
use App\Validators\ExchangeRateValidator;
use App\Validators\PurchaseOrderValidator;
use App\Validators\GoodsReceiptValidator;
use App\Validators\LandedCostValidator;
use App\Validators\SubcontractingValidator;
use App\Validators\SupportTicketValidator;
use App\Validators\CommunicationValidator;
use App\Validators\CampaignValidator;
use App\Validators\EmailCampaignValidator;
use App\Validators\WebhookPayloadValidator;
use App\Validators\SubscriptionValidator;
use App\Validators\QualityInspectionValidator;
use App\Validators\QualityParameterValidator;
use App\Validators\PriceListValidator;
use App\Validators\PaymentMethodValidator;
use App\Validators\InvoiceValidator;
use App\Validators\AssetValidator;
use App\Validators\DepreciationValidator;
use App\Validators\MaintenanceValidator;
use App\Validators\EmployeeValidator;
use App\Validators\LeaveValidator;
use App\Validators\PayrollValidator;
use App\Validators\ProjectValidator;
use App\Validators\TaskValidator;
use App\Validators\TimesheetValidator;
use App\Validators\StockEntryValidator;
use App\Validators\PickListValidator;
use App\Validators\PackingSlipValidator;
use App\Validators\ReturnValidator;
use App\Validators\OrderValidator;
use App\Validators\WebhookSubscriptionValidator;
use App\Validators\AttributeValidator;
use App\Validators\CustomerValidator;
use App\Repositories\Inventory\InventoryRepository;
use App\Repositories\Assets\AssetRepository;
use App\Repositories\Assets\DepreciationScheduleRepository;
use App\Repositories\Assets\MaintenanceRepository;
use App\Repositories\Jobs\JobRepository;
use App\Repositories\Jobs\SchedulerRuleRepository;
use App\Repositories\Portal\PortalUserRepository;
use App\Repositories\Portal\KnowledgeBaseRepository;
use App\Repositories\Notifications\NotificationRuleRepository;
use App\Repositories\Assignments\AssignmentRepository;
use App\Repositories\HR\EmployeeRepository;
use App\Repositories\HR\LeaveRepository;
use App\Repositories\HR\AttendanceRepository;
use App\Repositories\HR\PayrollRepository;
use App\Repositories\HR\SalarySlipRepository;
use App\Repositories\Projects\ProjectRepository;
use App\Repositories\Projects\TaskRepository;
use App\Repositories\Projects\TimesheetRepository;
use App\Repositories\Projects\ActivityTypeRepository;
use App\Repositories\Taxes\RegionalTaxRuleRepository;
use App\Repositories\Taxes\EInvoiceLogRepository;
use App\Repositories\Taxes\TaxCertificateRepository;
use App\Repositories\Inventory\StockEntryRepository;
use App\Repositories\Inventory\PickListRepository;
use App\Repositories\Inventory\PackingSlipRepository;
use App\Services\Inventory\InventoryService;
use App\Services\Assets\AssetService;
use App\Services\Assets\DepreciationService;
use App\Services\Assets\MaintenanceService;
use App\Services\Jobs\SchedulerService;
use App\Services\Jobs\JobRunnerService;
use App\Services\Portal\PortalService;
use App\Services\Portal\KnowledgeBaseService;
use App\Services\Notifications\NotificationService;
use App\Services\Assignments\AssignmentService;
use App\Services\HR\EmployeeService;
use App\Services\HR\LeaveService;
use App\Services\HR\AttendanceService;
use App\Services\HR\PayrollService;
use App\Services\Projects\ProjectService;
use App\Services\Projects\TaskService;
use App\Services\Projects\TimesheetService;
use App\Services\Projects\ActivityCostService;
use App\Services\Reports\ReportService;
use App\Services\Taxes\RegionalTaxService;
use App\Services\Taxes\WithholdingAdvancedService;
use App\Services\Inventory\StockEntryService;
use App\Services\Inventory\PickPackService;
use App\Services\Inventory\StockEntryReturnService;
use App\Validators\InventoryValidator;
use App\Validators\SchedulerValidator;
use App\Validators\JobValidator;
use App\Validators\PortalValidator;
use App\Validators\NotificationValidator;
use App\Validators\AssignmentValidator;
use App\Validators\ReportValidator;
use App\Validators\RegionalTaxValidator;
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
        return new ApprovalRepository(null, $db);
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
            static::approvalRequestValidator(false),
            static::orderRepository(false)
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

    public static function stockEntryValidator(bool $getShared = true): StockEntryValidator
    {
        return $getShared ? static::getSharedInstance('stockEntryValidator') : new StockEntryValidator();
    }

    public static function pickListValidator(bool $getShared = true): PickListValidator
    {
        return $getShared ? static::getSharedInstance('pickListValidator') : new PickListValidator();
    }

    public static function packingSlipValidator(bool $getShared = true): PackingSlipValidator
    {
        return $getShared ? static::getSharedInstance('packingSlipValidator') : new PackingSlipValidator();
    }

    public static function stockEntryRepository(bool $getShared = true): StockEntryRepository
    {
        if ($getShared) {
            return static::getSharedInstance('stockEntryRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new StockEntryRepository(null, null, $db);
    }

    public static function pickListRepository(bool $getShared = true): PickListRepository
    {
        if ($getShared) {
            return static::getSharedInstance('pickListRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new PickListRepository(null, null, $db);
    }

    public static function packingSlipRepository(bool $getShared = true): PackingSlipRepository
    {
        if ($getShared) {
            return static::getSharedInstance('packingSlipRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new PackingSlipRepository(null, null, $db);
    }

    public static function stockEntryService(bool $getShared = true): StockEntryService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('stockEntryService');
        }
        return new StockEntryService(
            static::stockEntryRepository(false),
            static::stockEntryValidator(false),
            static::stockLedgerService(false),
            static::inventoryRepository(false)
        );
    }

    public static function pickPackService(bool $getShared = true): PickPackService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('pickPackService');
        }
        return new PickPackService(
            static::pickListRepository(false),
            static::packingSlipRepository(false),
            static::stockEntryRepository(false),
            static::pickListValidator(false),
            static::packingSlipValidator(false)
        );
    }

    public static function stockEntryReturnService(bool $getShared = true): StockEntryReturnService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('stockEntryReturnService');
        }
        return new StockEntryReturnService(static::stockEntryService(false));
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

    public static function assetValidator(bool $getShared = true): AssetValidator
    {
        return $getShared ? static::getSharedInstance('assetValidator') : new AssetValidator();
    }

    public static function depreciationValidator(bool $getShared = true): DepreciationValidator
    {
        return $getShared ? static::getSharedInstance('depreciationValidator') : new DepreciationValidator();
    }

    public static function maintenanceValidator(bool $getShared = true): MaintenanceValidator
    {
        return $getShared ? static::getSharedInstance('maintenanceValidator') : new MaintenanceValidator();
    }

    public static function schedulerValidator(bool $getShared = true): SchedulerValidator
    {
        return $getShared ? static::getSharedInstance('schedulerValidator') : new SchedulerValidator();
    }

    public static function jobValidator(bool $getShared = true): JobValidator
    {
        return $getShared ? static::getSharedInstance('jobValidator') : new JobValidator();
    }

    public static function portalValidator(bool $getShared = true): PortalValidator
    {
        return $getShared ? static::getSharedInstance('portalValidator') : new PortalValidator();
    }

    public static function notificationValidator(bool $getShared = true): NotificationValidator
    {
        return $getShared ? static::getSharedInstance('notificationValidator') : new NotificationValidator();
    }

    public static function assignmentValidator(bool $getShared = true): AssignmentValidator
    {
        return $getShared ? static::getSharedInstance('assignmentValidator') : new AssignmentValidator();
    }

    public static function reportValidator(bool $getShared = true): ReportValidator
    {
        return $getShared ? static::getSharedInstance('reportValidator') : new ReportValidator();
    }

    public static function regionalTaxValidator(bool $getShared = true): RegionalTaxValidator
    {
        return $getShared ? static::getSharedInstance('regionalTaxValidator') : new RegionalTaxValidator();
    }

    public static function employeeValidator(bool $getShared = true): EmployeeValidator
    {
        return $getShared ? static::getSharedInstance('employeeValidator') : new EmployeeValidator();
    }

    public static function leaveValidator(bool $getShared = true): LeaveValidator
    {
        return $getShared ? static::getSharedInstance('leaveValidator') : new LeaveValidator();
    }

    public static function payrollValidator(bool $getShared = true): PayrollValidator
    {
        return $getShared ? static::getSharedInstance('payrollValidator') : new PayrollValidator();
    }

    public static function projectValidator(bool $getShared = true): ProjectValidator
    {
        return $getShared ? static::getSharedInstance('projectValidator') : new ProjectValidator();
    }

    public static function taskValidator(bool $getShared = true): TaskValidator
    {
        return $getShared ? static::getSharedInstance('taskValidator') : new TaskValidator();
    }

    public static function timesheetValidator(bool $getShared = true): TimesheetValidator
    {
        return $getShared ? static::getSharedInstance('timesheetValidator') : new TimesheetValidator();
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

    public static function assetRepository(bool $getShared = true): AssetRepository
    {
        if ($getShared) {
            return static::getSharedInstance('assetRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new AssetRepository(null, $db);
    }

    public static function depreciationScheduleRepository(bool $getShared = true): DepreciationScheduleRepository
    {
        if ($getShared) {
            return static::getSharedInstance('depreciationScheduleRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new DepreciationScheduleRepository(null, null, $db);
    }

    public static function maintenanceRepository(bool $getShared = true): MaintenanceRepository
    {
        if ($getShared) {
            return static::getSharedInstance('maintenanceRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new MaintenanceRepository(null, null, $db);
    }

    public static function jobRepository(bool $getShared = true): JobRepository
    {
        if ($getShared) {
            return static::getSharedInstance('jobRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new JobRepository(null, null, $db);
    }

    public static function schedulerRuleRepository(bool $getShared = true): SchedulerRuleRepository
    {
        if ($getShared) {
            return static::getSharedInstance('schedulerRuleRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new SchedulerRuleRepository(null, $db);
    }

    public static function portalUserRepository(bool $getShared = true): PortalUserRepository
    {
        if ($getShared) {
            return static::getSharedInstance('portalUserRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new PortalUserRepository(null, null, $db);
    }

    public static function knowledgeBaseRepository(bool $getShared = true): KnowledgeBaseRepository
    {
        if ($getShared) {
            return static::getSharedInstance('knowledgeBaseRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new KnowledgeBaseRepository(null, null, $db);
    }

    public static function notificationRuleRepository(bool $getShared = true): NotificationRuleRepository
    {
        if ($getShared) {
            return static::getSharedInstance('notificationRuleRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new NotificationRuleRepository(null, null, $db);
    }

    public static function assignmentRepository(bool $getShared = true): AssignmentRepository
    {
        if ($getShared) {
            return static::getSharedInstance('assignmentRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new AssignmentRepository(null, null, $db);
    }

    public static function employeeRepository(bool $getShared = true): EmployeeRepository
    {
        if ($getShared) {
            return static::getSharedInstance('employeeRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new EmployeeRepository(null, $db);
    }

    public static function leaveRepository(bool $getShared = true): LeaveRepository
    {
        if ($getShared) {
            return static::getSharedInstance('leaveRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new LeaveRepository(null, null, $db);
    }

    public static function attendanceRepository(bool $getShared = true): AttendanceRepository
    {
        if ($getShared) {
            return static::getSharedInstance('attendanceRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new AttendanceRepository(null, $db);
    }

    public static function payrollRepository(bool $getShared = true): PayrollRepository
    {
        if ($getShared) {
            return static::getSharedInstance('payrollRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new PayrollRepository(null, $db);
    }

    public static function salarySlipRepository(bool $getShared = true): SalarySlipRepository
    {
        if ($getShared) {
            return static::getSharedInstance('salarySlipRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new SalarySlipRepository(null, null, $db);
    }

    public static function regionalTaxRuleRepository(bool $getShared = true): RegionalTaxRuleRepository
    {
        if ($getShared) {
            return static::getSharedInstance('regionalTaxRuleRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new RegionalTaxRuleRepository(null, $db);
    }

    public static function eInvoiceLogRepository(bool $getShared = true): EInvoiceLogRepository
    {
        if ($getShared) {
            return static::getSharedInstance('eInvoiceLogRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new EInvoiceLogRepository(null, $db);
    }

    public static function taxCertificateRepository(bool $getShared = true): TaxCertificateRepository
    {
        if ($getShared) {
            return static::getSharedInstance('taxCertificateRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new TaxCertificateRepository(null, $db);
    }

    public static function projectRepository(bool $getShared = true): ProjectRepository
    {
        if ($getShared) {
            return static::getSharedInstance('projectRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ProjectRepository(null, $db);
    }

    public static function taskRepository(bool $getShared = true): TaskRepository
    {
        if ($getShared) {
            return static::getSharedInstance('taskRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new TaskRepository(null, $db);
    }

    public static function timesheetRepository(bool $getShared = true): TimesheetRepository
    {
        if ($getShared) {
            return static::getSharedInstance('timesheetRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new TimesheetRepository(null, null, $db);
    }

    public static function activityTypeRepository(bool $getShared = true): ActivityTypeRepository
    {
        if ($getShared) {
            return static::getSharedInstance('activityTypeRepository');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ActivityTypeRepository(null, $db);
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

    public static function assetService(bool $getShared = true): AssetService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('assetService');
        }
        return new AssetService(
            static::assetRepository(false),
            static::assetValidator(false)
        );
    }

    public static function depreciationService(bool $getShared = true): DepreciationService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('depreciationService');
        }
        return new DepreciationService(
            static::depreciationScheduleRepository(false),
            static::assetRepository(false),
            static::glEntryRepository(false),
            static::depreciationValidator(false)
        );
    }

    public static function maintenanceService(bool $getShared = true): MaintenanceService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('maintenanceService');
        }
        return new MaintenanceService(
            static::maintenanceRepository(false),
            static::assetRepository(false),
            static::maintenanceValidator(false),
            static::stockEntryService(false)
        );
    }

    public static function employeeService(bool $getShared = true): EmployeeService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('employeeService');
        }
        return new EmployeeService(
            static::employeeRepository(false),
            static::employeeValidator(false)
        );
    }

    public static function leaveService(bool $getShared = true): LeaveService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('leaveService');
        }
        return new LeaveService(
            static::leaveRepository(false),
            static::employeeRepository(false),
            static::leaveValidator(false)
        );
    }

    public static function attendanceService(bool $getShared = true): AttendanceService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('attendanceService');
        }
        return new AttendanceService(
            static::attendanceRepository(false),
            static::employeeRepository(false)
        );
    }

    public static function payrollService(bool $getShared = true): PayrollService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('payrollService');
        }
        return new PayrollService(
            static::payrollRepository(false),
            static::salarySlipRepository(false),
            static::employeeRepository(false),
            static::glEntryRepository(false),
            static::payrollValidator(false)
        );
    }

    public static function schedulerService(bool $getShared = true): SchedulerService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('schedulerService');
        }
        return new SchedulerService(
            static::schedulerRuleRepository(false),
            static::jobRepository(false),
            static::schedulerValidator(false),
            static::jobValidator(false)
        );
    }

    public static function jobRunnerService(bool $getShared = true): JobRunnerService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('jobRunnerService');
        }
        return new JobRunnerService(static::jobRepository(false));
    }

    public static function portalService(bool $getShared = true): PortalService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('portalService');
        }
        return new PortalService(
            static::portalUserRepository(false),
            static::portalValidator(false)
        );
    }

    public static function knowledgeBaseService(bool $getShared = true): KnowledgeBaseService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('knowledgeBaseService');
        }
        return new KnowledgeBaseService(static::knowledgeBaseRepository(false));
    }

    public static function notificationService(bool $getShared = true): NotificationService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('notificationService');
        }
        return new NotificationService(
            static::notificationRuleRepository(false),
            static::notificationValidator(false)
        );
    }

    public static function assignmentService(bool $getShared = true): AssignmentService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('assignmentService');
        }
        return new AssignmentService(
            static::assignmentRepository(false),
            static::assignmentValidator(false)
        );
    }

    public static function reportService(bool $getShared = true): ReportService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('reportService');
        }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ReportService($db, static::reportValidator(false));
    }

    public static function regionalTaxService(bool $getShared = true): RegionalTaxService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('regionalTaxService');
        }
        return new RegionalTaxService(
            static::regionalTaxRuleRepository(false),
            static::eInvoiceLogRepository(false),
            static::regionalTaxValidator(false)
        );
    }

    public static function withholdingAdvancedService(bool $getShared = true): WithholdingAdvancedService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('withholdingAdvancedService');
        }
        return new WithholdingAdvancedService(
            static::taxCertificateRepository(false),
            static::regionalTaxValidator(false)
        );
    }

    public static function projectService(bool $getShared = true): ProjectService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('projectService');
        }
        return new ProjectService(
            static::projectRepository(false),
            static::taskRepository(false),
            static::projectValidator(false)
        );
    }

    public static function taskService(bool $getShared = true): TaskService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('taskService');
        }
        return new TaskService(
            static::taskRepository(false),
            static::taskValidator(false),
            static::projectService(false)
        );
    }

    public static function timesheetService(bool $getShared = true): TimesheetService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('timesheetService');
        }
        return new TimesheetService(
            static::timesheetRepository(false),
            static::activityTypeRepository(false),
            static::taskRepository(false),
            static::timesheetValidator(false)
        );
    }

    public static function activityCostService(bool $getShared = true): ActivityCostService
    {
        if ($getShared && ENVIRONMENT !== 'testing') {
            return static::getSharedInstance('activityCostService');
        }
        return new ActivityCostService(static::activityTypeRepository(false));
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

    public static function leadRepository(bool $getShared = true): LeadRepository
    {
        if ($getShared) { return static::getSharedInstance('leadRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new LeadRepository(null, $db);
    }

    public static function leadValidator(bool $getShared = true): LeadValidator
    {
        return $getShared ? static::getSharedInstance('leadValidator') : new LeadValidator();
    }

    public static function leadService(bool $getShared = true): LeadService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('leadService'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new LeadService(static::leadRepository(false), static::leadValidator(false), $db);
    }

    public static function opportunityRepository(bool $getShared = true): OpportunityRepository
    {
        if ($getShared) { return static::getSharedInstance('opportunityRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new OpportunityRepository(null, null, $db);
    }

    public static function opportunityValidator(bool $getShared = true): OpportunityValidator
    {
        return $getShared ? static::getSharedInstance('opportunityValidator') : new OpportunityValidator();
    }

    public static function opportunityService(bool $getShared = true): OpportunityService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('opportunityService'); }
        return new OpportunityService(
            static::opportunityRepository(false),
            static::opportunityValidator(false),
            static::pricingService(false)
        );
    }

    public static function quotationRepository(bool $getShared = true): QuotationRepository
    {
        if ($getShared) { return static::getSharedInstance('quotationRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new QuotationRepository(null, null, $db);
    }

    public static function quotationValidator(bool $getShared = true): QuotationValidator
    {
        return $getShared ? static::getSharedInstance('quotationValidator') : new QuotationValidator();
    }

    public static function quotationNumberGenerator(bool $getShared = true): QuotationNumberGenerator
    {
        return $getShared ? static::getSharedInstance('quotationNumberGenerator') : new QuotationNumberGenerator();
    }

    public static function quotationService(bool $getShared = true): QuotationService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('quotationService'); }
        return new QuotationService(
            static::quotationRepository(false),
            static::quotationValidator(false),
            static::pricingService(false),
            static::quotationNumberGenerator(false)
        );
    }

    public static function contractRepository(bool $getShared = true): ContractRepository
    {
        if ($getShared) { return static::getSharedInstance('contractRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ContractRepository(null, null, $db);
    }

    public static function contractTemplateRepository(bool $getShared = true): ContractTemplateRepository
    {
        if ($getShared) { return static::getSharedInstance('contractTemplateRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ContractTemplateRepository(null, $db);
    }

    public static function contractValidator(bool $getShared = true): ContractValidator
    {
        return $getShared ? static::getSharedInstance('contractValidator') : new ContractValidator();
    }

    public static function contractService(bool $getShared = true): ContractService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('contractService'); }
        return new ContractService(
            static::contractRepository(false),
            static::contractTemplateRepository(false),
            static::contractValidator(false)
        );
    }

    public static function appointmentRepository(bool $getShared = true): AppointmentRepository
    {
        if ($getShared) { return static::getSharedInstance('appointmentRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new AppointmentRepository(null, $db);
    }

    public static function appointmentValidator(bool $getShared = true): AppointmentValidator
    {
        return $getShared ? static::getSharedInstance('appointmentValidator') : new AppointmentValidator();
    }

    public static function appointmentService(bool $getShared = true): AppointmentService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('appointmentService'); }
        return new AppointmentService(
            static::appointmentRepository(false),
            static::appointmentValidator(false)
        );
    }

    public static function coaRepository(bool $getShared = true): COARepository
    {
        if ($getShared) { return static::getSharedInstance('coaRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new COARepository(null, $db);
    }

    public static function glEntryRepository(bool $getShared = true): GLEntryRepository
    {
        if ($getShared) { return static::getSharedInstance('glEntryRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new GLEntryRepository(null, $db);
    }

    public static function coaValidator(bool $getShared = true): COAValidator
    {
        return $getShared ? static::getSharedInstance('coaValidator') : new COAValidator();
    }

    public static function glEntryValidator(bool $getShared = true): GLEntryValidator
    {
        return $getShared ? static::getSharedInstance('glEntryValidator') : new GLEntryValidator();
    }

    public static function coaService(bool $getShared = true): COAService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('coaService'); }
        return new COAService(static::coaRepository(false), static::coaValidator(false));
    }

    public static function accountingService(bool $getShared = true): AccountingService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('accountingService'); }
        return new AccountingService(
            static::coaRepository(false),
            static::glEntryRepository(false),
            static::glEntryValidator(false)
        );
    }

    public static function salesInvoiceRepository(bool $getShared = true): SalesInvoiceRepository
    {
        if ($getShared) { return static::getSharedInstance('salesInvoiceRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new SalesInvoiceRepository(null, null, null, null, $db);
    }

    public static function salesInvoiceValidator(bool $getShared = true): SalesInvoiceValidator
    {
        return $getShared ? static::getSharedInstance('salesInvoiceValidator') : new SalesInvoiceValidator();
    }

    public static function salesInvoiceService(bool $getShared = true): SalesInvoiceService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('salesInvoiceService'); }
        return new SalesInvoiceService(
            static::salesInvoiceRepository(false),
            static::salesInvoiceValidator(false),
            static::taxTemplateRepository(false),
            static::accountingService(false)
        );
    }

    public static function purchaseInvoiceRepository(bool $getShared = true): PurchaseInvoiceRepository
    {
        if ($getShared) { return static::getSharedInstance('purchaseInvoiceRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new PurchaseInvoiceRepository(null, null, null, $db);
    }

    public static function purchaseInvoiceValidator(bool $getShared = true): PurchaseInvoiceValidator
    {
        return $getShared ? static::getSharedInstance('purchaseInvoiceValidator') : new PurchaseInvoiceValidator();
    }

    public static function purchaseInvoiceService(bool $getShared = true): PurchaseInvoiceService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('purchaseInvoiceService'); }
        return new PurchaseInvoiceService(
            static::purchaseInvoiceRepository(false),
            static::purchaseInvoiceValidator(false),
            static::taxTemplateRepository(false),
            static::accountingService(false)
        );
    }

    public static function accountingPaymentEntryRepository(bool $getShared = true): AccountingPaymentEntryRepository
    {
        if ($getShared) { return static::getSharedInstance('accountingPaymentEntryRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new AccountingPaymentEntryRepository(null, null, $db);
    }

    public static function bankStatementRepository(bool $getShared = true): BankStatementRepository
    {
        if ($getShared) { return static::getSharedInstance('bankStatementRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new BankStatementRepository(null, $db);
    }

    public static function bankReconciliationRepository(bool $getShared = true): BankReconciliationRepository
    {
        if ($getShared) { return static::getSharedInstance('bankReconciliationRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new BankReconciliationRepository(null, null, $db);
    }

    public static function withholdingRuleRepository(bool $getShared = true): WithholdingRuleRepository
    {
        if ($getShared) { return static::getSharedInstance('withholdingRuleRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new WithholdingRuleRepository(null, $db);
    }

    public static function creditLimitRepository(bool $getShared = true): CreditLimitRepository
    {
        if ($getShared) { return static::getSharedInstance('creditLimitRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new CreditLimitRepository(null, $db);
    }

    public static function taxTemplateItemRepository(bool $getShared = true): TaxTemplateItemRepository
    {
        if ($getShared) { return static::getSharedInstance('taxTemplateItemRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new TaxTemplateItemRepository(null, $db);
    }

    public static function bankReconciliationValidator(bool $getShared = true): BankReconciliationValidator
    {
        return $getShared ? static::getSharedInstance('bankReconciliationValidator') : new BankReconciliationValidator();
    }

    public static function withholdingValidator(bool $getShared = true): WithholdingValidator
    {
        return $getShared ? static::getSharedInstance('withholdingValidator') : new WithholdingValidator();
    }

    public static function creditControlValidator(bool $getShared = true): CreditControlValidator
    {
        return $getShared ? static::getSharedInstance('creditControlValidator') : new CreditControlValidator();
    }

    public static function accountingPaymentEntryService(bool $getShared = true): AccountingPaymentEntryService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('accountingPaymentEntryService'); }
        return new AccountingPaymentEntryService(
            static::accountingPaymentEntryRepository(false),
            static::paymentEntryValidator(false),
            static::accountingService(false)
        );
    }

    public static function bankReconciliationService(bool $getShared = true): BankReconciliationService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('bankReconciliationService'); }
        return new BankReconciliationService(
            static::bankStatementRepository(false),
            static::bankReconciliationRepository(false),
            static::accountingPaymentEntryRepository(false),
            static::bankReconciliationValidator(false)
        );
    }

    public static function withholdingService(bool $getShared = true): WithholdingService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('withholdingService'); }
        return new WithholdingService(
            static::withholdingRuleRepository(false),
            static::withholdingValidator(false)
        );
    }

    public static function creditControlService(bool $getShared = true): CreditControlService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('creditControlService'); }
        return new CreditControlService(
            static::creditLimitRepository(false),
            static::creditControlValidator(false)
        );
    }

    public static function taxTemplateService(bool $getShared = true): TaxTemplateService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('taxTemplateService'); }
        return new TaxTemplateService(
            static::taxTemplateRepository(false),
            static::taxTemplateItemRepository(false),
            static::taxTemplateValidator(false)
        );
    }

    public static function purchaseOrderRepository(bool $getShared = true): PurchaseOrderRepository
    {
        if ($getShared) { return static::getSharedInstance('purchaseOrderRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new PurchaseOrderRepository(null, null, $db);
    }

    public static function goodsReceiptRepository(bool $getShared = true): GoodsReceiptRepository
    {
        if ($getShared) { return static::getSharedInstance('goodsReceiptRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new GoodsReceiptRepository(null, null, $db);
    }

    public static function landedCostRepository(bool $getShared = true): LandedCostRepository
    {
        if ($getShared) { return static::getSharedInstance('landedCostRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new LandedCostRepository(null, null, $db);
    }

    public static function subcontractingRepository(bool $getShared = true): SubcontractingRepository
    {
        if ($getShared) { return static::getSharedInstance('subcontractingRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new SubcontractingRepository(null, null, $db);
    }

    public static function purchaseOrderValidator(bool $getShared = true): PurchaseOrderValidator
    {
        return $getShared ? static::getSharedInstance('purchaseOrderValidator') : new PurchaseOrderValidator();
    }

    public static function goodsReceiptValidator(bool $getShared = true): GoodsReceiptValidator
    {
        return $getShared ? static::getSharedInstance('goodsReceiptValidator') : new GoodsReceiptValidator();
    }

    public static function landedCostValidator(bool $getShared = true): LandedCostValidator
    {
        return $getShared ? static::getSharedInstance('landedCostValidator') : new LandedCostValidator();
    }

    public static function subcontractingValidator(bool $getShared = true): SubcontractingValidator
    {
        return $getShared ? static::getSharedInstance('subcontractingValidator') : new SubcontractingValidator();
    }

    public static function purchaseOrderService(bool $getShared = true): PurchaseOrderService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('purchaseOrderService'); }
        return new PurchaseOrderService(
            static::purchaseOrderRepository(false),
            static::purchaseOrderValidator(false)
        );
    }

    public static function goodsReceiptService(bool $getShared = true): GoodsReceiptService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('goodsReceiptService'); }
        return new GoodsReceiptService(
            static::goodsReceiptRepository(false),
            static::purchaseOrderRepository(false),
            static::goodsReceiptValidator(false),
            static::stockLedgerService(false)
        );
    }

    public static function landedCostService(bool $getShared = true): LandedCostService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('landedCostService'); }
        return new LandedCostService(
            static::landedCostRepository(false),
            static::landedCostValidator(false),
            static::goodsReceiptRepository(false)
        );
    }

    public static function subcontractingService(bool $getShared = true): SubcontractingService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('subcontractingService'); }
        return new SubcontractingService(
            static::subcontractingRepository(false),
            static::subcontractingValidator(false),
            static::stockLedgerService(false)
        );
    }

    public static function currencyService(bool $getShared = true): CurrencyService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('currencyService'); }
        return new CurrencyService(
            static::exchangeRateRepository(false),
            static::exchangeRateValidator(false)
        );
    }

    public static function exchangeRateRepository(bool $getShared = true): ExchangeRateRepository
    {
        if ($getShared) { return static::getSharedInstance('exchangeRateRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ExchangeRateRepository(null, $db);
    }

    public static function exchangeRateValidator(bool $getShared = true): ExchangeRateValidator
    {
        return $getShared ? static::getSharedInstance('exchangeRateValidator') : new ExchangeRateValidator();
    }

    public static function agingService(bool $getShared = true): AgingService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('agingService'); }
        return new AgingService(static::glEntryRepository(false), static::currencyService(false));
    }

    public static function supportTicketRepository(bool $getShared = true): SupportTicketRepository
    {
        if ($getShared) { return static::getSharedInstance('supportTicketRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new SupportTicketRepository(null, $db);
    }

    public static function ticketEventRepository(bool $getShared = true): TicketEventRepository
    {
        if ($getShared) { return static::getSharedInstance('ticketEventRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new TicketEventRepository(null, $db);
    }

    public static function communicationRepository(bool $getShared = true): CommunicationRepository
    {
        if ($getShared) { return static::getSharedInstance('communicationRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new CommunicationRepository(null, $db);
    }

    public static function supportTicketValidator(bool $getShared = true): SupportTicketValidator
    {
        return $getShared ? static::getSharedInstance('supportTicketValidator') : new SupportTicketValidator();
    }

    public static function communicationValidator(bool $getShared = true): CommunicationValidator
    {
        return $getShared ? static::getSharedInstance('communicationValidator') : new CommunicationValidator();
    }

    public static function supportTicketService(bool $getShared = true): SupportTicketService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('supportTicketService'); }
        return new SupportTicketService(
            static::supportTicketRepository(false),
            static::ticketEventRepository(false),
            static::supportTicketValidator(false)
        );
    }

    public static function communicationService(bool $getShared = true): CommunicationService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('communicationService'); }
        return new CommunicationService(
            static::communicationRepository(false),
            static::supportTicketRepository(false),
            static::ticketEventRepository(false),
            static::communicationValidator(false)
        );
    }

    public static function campaignRepository(bool $getShared = true): CampaignRepository
    {
        if ($getShared) { return static::getSharedInstance('campaignRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new CampaignRepository(null, null, $db);
    }

    public static function campaignValidator(bool $getShared = true): CampaignValidator
    {
        return $getShared ? static::getSharedInstance('campaignValidator') : new CampaignValidator();
    }

    public static function campaignService(bool $getShared = true): CampaignService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('campaignService'); }
        return new CampaignService(static::campaignRepository(false), static::campaignValidator(false));
    }

    public static function emailCampaignRepository(bool $getShared = true): EmailCampaignRepository
    {
        if ($getShared) { return static::getSharedInstance('emailCampaignRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new EmailCampaignRepository(null, null, $db);
    }

    public static function emailCampaignValidator(bool $getShared = true): EmailCampaignValidator
    {
        return $getShared ? static::getSharedInstance('emailCampaignValidator') : new EmailCampaignValidator();
    }

    public static function emailCampaignService(bool $getShared = true): EmailCampaignService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('emailCampaignService'); }
        return new EmailCampaignService(
            static::emailCampaignRepository(false),
            static::campaignRepository(false),
            static::emailCampaignValidator(false)
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

    public static function companyRepository(bool $getShared = true): CompanyRepository
    {
        if ($getShared) { return static::getSharedInstance('companyRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new CompanyRepository(null, null, $db);
    }

    public static function shareRepository(bool $getShared = true): ShareRepository
    {
        if ($getShared) { return static::getSharedInstance('shareRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new ShareRepository(null, $db);
    }

    public static function auditLogRepository(bool $getShared = true): AuditLogRepository
    {
        if ($getShared) { return static::getSharedInstance('auditLogRepository'); }
        $db = \Config\Database::connect(ENVIRONMENT === 'testing' ? 'tests' : null);
        return new AuditLogRepository(null, $db);
    }

    public static function permissionValidator(bool $getShared = true): PermissionValidator
    {
        return $getShared ? static::getSharedInstance('permissionValidator') : new PermissionValidator();
    }

    public static function companyValidator(bool $getShared = true): CompanyValidator
    {
        return $getShared ? static::getSharedInstance('companyValidator') : new CompanyValidator(static::permissionValidator(false));
    }

    public static function shareValidator(bool $getShared = true): ShareValidator
    {
        return $getShared ? static::getSharedInstance('shareValidator') : new ShareValidator(static::permissionValidator(false));
    }

    public static function permissionService(bool $getShared = true): PermissionService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('permissionService'); }
        return new PermissionService(
            static::companyRepository(false),
            static::shareRepository(false),
            static::permissionValidator(false)
        );
    }

    public static function auditService(bool $getShared = true): AuditService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('auditService'); }
        return new AuditService(
            static::auditLogRepository(false),
            static::permissionService(false)
        );
    }

    public static function sharingService(bool $getShared = true): SharingService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('sharingService'); }
        return new SharingService(
            static::shareRepository(false),
            static::shareValidator(false),
            static::permissionService(false),
            static::auditService(false)
        );
    }

    public static function companyService(bool $getShared = true): CompanyService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('companyService'); }
        return new CompanyService(
            static::companyRepository(false),
            static::companyValidator(false),
            static::permissionValidator(false),
            static::permissionService(false)
        );
    }

    public static function documentAccessService(bool $getShared = true): DocumentAccessService
    {
        if ($getShared && ENVIRONMENT !== 'testing') { return static::getSharedInstance('documentAccessService'); }
        return new DocumentAccessService(
            static::permissionService(false),
            static::auditService(false)
        );
    }
}
