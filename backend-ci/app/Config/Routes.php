<?php
namespace Config;

use CodeIgniter\Config\Services;
use CodeIgniter\Router\RouteCollectionInterface;

$routes = Services::routes();
// Default
$routes->setDefaultNamespace('App\\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();

if (is_file(SYSTEMPATH . 'Config/Routes.php')) {
    require SYSTEMPATH . 'Config/Routes.php';
}

$routes->group('api', static function (RouteCollectionInterface $routes) {
    $routes->options('(:any)', static function () {
        return service('response')->setStatusCode(200);
    });
    $routes->post('auth/login', 'Api\\AuthController::login');
    // Backward-compat alias for legacy FE calling /users/login
    $routes->post('users/login', 'Api\\AuthController::login');
    $routes->get('health', 'Api\\HealthController::index');
    // TEMP DEBUG - DELETE AFTER DEBUGGING
    $routes->get('debug/debt-check', 'Api\\DebugController::debtCheck');
    $routes->get('dashboard/kpi-today', 'Api\\DashboardController::kpiToday');
    $routes->get('dashboard/revenue-chart', 'Api\\DashboardController::revenueChart');
    $routes->get('dashboard/top-products', 'Api\\DashboardController::topProducts');
    $routes->get('dashboard/top-customers', 'Api\\DashboardController::topCustomers');
    $routes->get('dashboard/activities', 'Api\\DashboardController::activities');
    $routes->get('products', 'Api\\ProductsController::index');
    $routes->get('products/(:num)', 'Api\\ProductsController::show/$1');
    $routes->get('products/(:num)/detail-with-variants', 'Api\\ProductsController::detailWithVariants/$1');
    $routes->get('products/(:num)/variants', 'Api\\ProductsController::variants/$1');
    $routes->post('products/(:num)/variants', 'Api\\ProductVariantsController::create/$1');
    $routes->get('products/(:num)/images', 'Api\\ProductsController::images/$1');
    $routes->post('products/upload-multiple', 'Api\\ProductsController::uploadMultiple');
    $routes->post('products/upload', 'Api\\ProductsController::uploadSingle');
    $routes->post('products/(:num)/images/attach-multiple', 'Api\\ProductsController::attachImages/$1');
    $routes->put('products/images/(:num)/set-primary', 'Api\\ProductsController::setPrimaryImage/$1');
    $routes->delete('products/images/(:num)', 'Api\\ProductsController::deleteImage/$1');
    $routes->get('products/(:num)/used-attribute-options', 'Api\\ProductsController::usedAttributeOptions/$1');
    $routes->get('products/(:num)/attribute-values', 'Api\\ProductsController::productAttributeValues/$1');
    $routes->post('products/(:num)/attribute-values', 'Api\\ProductsController::updateProductAttributeValues/$1');
    $routes->delete('products/(:num)/attribute-values/(:num)', 'Api\\ProductsController::removeAttributeFromProduct/$1/$2');
    $routes->post('products', 'Api\\ProductsController::create');
    $routes->put('products/(:num)', 'Api\\ProductsController::update/$1');
    $routes->delete('products/(:num)', 'Api\\ProductsController::delete/$1');
    $routes->post('products/check-code', 'Api\\ProductsController::checkCode');
    $routes->post('products/import', 'Api\\ProductsController::import');
    $routes->get('products/export', 'Api\\ProductsController::export');
    $routes->get('products/import/template', 'Api\\ProductsController::importTemplate');
    $routes->get('products/(:num)/analytics', 'Api\\ProductsController::analytics/$1');
    $routes->get('products/media/library', 'Api\\ProductMediaController::library');
    $routes->get('products/media/by-date', 'Api\\ProductMediaController::byDate');
    $routes->get('products/media/search-sku', 'Api\\ProductMediaController::searchSku');
    $routes->get('delivery-notes', 'Api\\DeliveryNotesController::index');
    $routes->get('delivery-notes/(:num)', 'Api\\DeliveryNotesController::show/$1');
    $routes->post('delivery-notes', 'Api\\DeliveryNotesController::create');
    $routes->post('delivery-notes/from-order', 'Api\\DeliveryNotesController::createFromOrder');
    $routes->post('delivery-notes/(:num)/confirm', 'Api\\DeliveryNotesController::confirm/$1');
    $routes->post('delivery-notes/(:num)/ship', 'Api\\DeliveryNotesController::ship/$1');
    $routes->post('delivery-notes/(:num)/deliver', 'Api\\DeliveryNotesController::deliver/$1');
    $routes->post('delivery-notes/(:num)/cancel', 'Api\\DeliveryNotesController::cancel/$1');
    $routes->get('product-batches', 'Api\\ProductBatchesController::index');
    $routes->get('product-batches/expiring', 'Api\\ProductBatchesController::expiring');
    $routes->get('product-batches/(:num)', 'Api\\ProductBatchesController::show/$1');
    $routes->post('product-batches', 'Api\\ProductBatchesController::create');
    $routes->put('product-batches/(:num)', 'Api\\ProductBatchesController::update/$1');
    $routes->post('product-batches/(:num)/adjust-quantity', 'Api\\ProductBatchesController::adjust/$1');
    $routes->get('product-serials', 'Api\\ProductSerialsController::index');
    $routes->post('product-serials', 'Api\\ProductSerialsController::create');
    $routes->post('product-serials/reserve', 'Api\\ProductSerialsController::reserve');
    $routes->post('product-serials/sell', 'Api\\ProductSerialsController::sell');
    $routes->post('product-serials/return', 'Api\\ProductSerialsController::markReturned');
    $routes->get('approval-rules', 'Api\\ApprovalRulesController::index');
    $routes->post('approval-rules', 'Api\\ApprovalRulesController::create');
    $routes->put('approval-rules/(:num)', 'Api\\ApprovalRulesController::update/$1');
    $routes->post('approvals/submit', 'Api\\ApprovalsController::submit');
    $routes->post('approvals/(:num)/approve', 'Api\\ApprovalsController::approve/$1');
    $routes->post('approvals/(:num)/reject', 'Api\\ApprovalsController::reject/$1');
    $routes->get('pricing-rules', 'Api\\PricingRulesController::index');
    $routes->post('pricing-rules', 'Api\\PricingRulesController::create');
    $routes->put('pricing-rules/(:num)', 'Api\\PricingRulesController::update/$1');
    $routes->post('pricing/preview', 'Api\\PricingController::preview');
    $routes->get('stock-reconciliations', 'Api\\StockReconciliationsController::index');
    $routes->get('stock-reconciliations/(:num)', 'Api\\StockReconciliationsController::show/$1');
    $routes->post('stock-reconciliations', 'Api\\StockReconciliationsController::create');
    $routes->post('stock-reconciliations/(:num)/submit', 'Api\\StockReconciliationsController::submit/$1');
    $routes->post('stock-reconciliations/(:num)/approve', 'Api\\StockReconciliationsController::approve/$1');
    $routes->post('stock-reconciliations/(:num)/reject', 'Api\\StockReconciliationsController::reject/$1');
    $routes->get('stock-entries/(:num)', 'Api\\StockEntriesController::show/$1');
    $routes->post('stock-entries', 'Api\\StockEntriesController::create');
    $routes->post('stock-entries/(:num)/submit', 'Api\\StockEntriesController::submit/$1');
    $routes->post('stock-entries/(:num)/cancel', 'Api\\StockEntriesController::cancel/$1');
    $routes->post('stock-entries/returns', 'Api\\StockEntryReturnsController::create');

    // Stock Transfers (Inventory Transfer)
    $routes->get('inventory/transfers', 'Api\\StockTransfersController::index');
    $routes->get('inventory/transfers/(:segment)', 'Api\\StockTransfersController::show/$1');
    $routes->post('inventory/transfers', 'Api\\StockTransfersController::create');
    $routes->put('inventory/transfers/(:num)', 'Api\\StockTransfersController::update/$1');
    $routes->post('inventory/transfers/(:num)/submit', 'Api\\StockTransfersController::submit/$1');
    $routes->post('inventory/transfers/(:num)/receive', 'Api\\StockTransfersController::receive/$1');
    $routes->post('inventory/transfers/(:num)/cancel', 'Api\\StockTransfersController::cancel/$1');
    $routes->post('inventory/transfers/(:segment)/open', 'Api\\StockTransfersController::open/$1');
    $routes->post('inventory/transfers/(:segment)/duplicate', 'Api\\StockTransfersController::duplicate/$1');
    $routes->post('inventory/transfers/(:segment)/notes', 'Api\\StockTransfersController::saveNotes/$1');

    // Stock Audits (Inventory Audit / Kiểm kho)
    $routes->get('inventory/stock-audits', 'Api\\StockAuditsController::index');
    $routes->get('inventory/stock-audits/(:segment)', 'Api\\StockAuditsController::show/$1');
    $routes->post('inventory/stock-audits', 'Api\\StockAuditsController::create');
    $routes->put('inventory/stock-audits/(:num)', 'Api\\StockAuditsController::update/$1');
    $routes->post('inventory/stock-audits/(:num)/complete', 'Api\\StockAuditsController::complete/$1');
    $routes->post('inventory/stock-audits/(:num)/cancel', 'Api\\StockAuditsController::cancel/$1');

    // Stock Disposals (Xuất hủy hàng)
    $routes->get('inventory/disposals', 'Api\\StockDisposalsController::index');
    $routes->get('inventory/disposals/(:segment)', 'Api\\StockDisposalsController::show/$1');
    $routes->post('inventory/disposals', 'Api\\StockDisposalsController::create');
    $routes->put('inventory/disposals/(:num)', 'Api\\StockDisposalsController::update/$1');
    $routes->post('inventory/disposals/(:num)/complete', 'Api\\StockDisposalsController::complete/$1');
    $routes->post('inventory/disposals/(:num)/cancel', 'Api\\StockDisposalsController::cancel/$1');


    $routes->post('pick-lists', 'Api\\PickListsController::create');
    $routes->get('pick-lists/(:num)', 'Api\\PickListsController::show/$1');
    $routes->post('packing-slips', 'Api\\PackingSlipsController::create');
    $routes->get('packing-slips/(:num)', 'Api\\PackingSlipsController::show/$1');
    $routes->post('portal/login', 'Api\\PortalAuthController::login');
    $routes->post('portal/users', 'Api\\PortalUsersController::create');
    $routes->get('knowledge-base/categories', 'Api\\KnowledgeBaseController::categories');
    $routes->get('knowledge-base/articles', 'Api\\KnowledgeBaseController::articles');
    $routes->get('knowledge-base/articles/(:num)', 'Api\\KnowledgeBaseController::show/$1');
    $routes->post('notification-rules', 'Api\\NotificationController::createRule');
    $routes->get('notification-rules', 'Api\\NotificationController::listRules');
    $routes->post('notifications/trigger', 'Api\\NotificationController::trigger');
    $routes->post('assignment-rules', 'Api\\AssignmentsController::createRule');
    $routes->get('assignment-rules', 'Api\\AssignmentsController::listRules');
    $routes->post('assignment-rules/assign', 'Api\\AssignmentsController::assign');
    $routes->get('companies', 'Api\\CompaniesController::index');
    $routes->post('companies', 'Api\\CompaniesController::create');
    $routes->put('companies/(:num)', 'Api\\CompaniesController::update/$1');
    $routes->post('companies/(:num)/permissions', 'Api\\CompaniesController::assignPermission/$1');
    $routes->get('companies/(:num)/permissions', 'Api\\CompaniesController::permissions/$1');
    $routes->get('shares', 'Api\\SharesController::index');
    $routes->post('shares', 'Api\\SharesController::create');
    $routes->delete('shares/(:num)', 'Api\\SharesController::delete/$1');
    $routes->get('documents/(:num)/(:segment)/(:num)', 'Api\\CompanyDocumentsController::show/$1/$2/$3');
    $routes->put('documents/(:num)/(:segment)/(:num)', 'Api\\CompanyDocumentsController::update/$1/$2/$3');
    $routes->get('audit-logs', 'Api\\AuditLogsController::index');
    $routes->post('scheduler-rules', 'Api\\SchedulerController::createRule');
    $routes->get('scheduler-rules', 'Api\\SchedulerController::listRules');
    $routes->post('scheduler/tick', 'Api\\SchedulerController::tick');
    $routes->post('jobs/enqueue', 'Api\\JobsController::enqueue');
    $routes->post('jobs/run-next', 'Api\\JobsController::runNext');
    $routes->get('reports/gl', 'Api\\ReportsController::gl');
    $routes->get('reports/profit-loss', 'Api\\ReportsController::profitLoss');
    $routes->get('reports/balance-sheet', 'Api\\ReportsController::balanceSheet');
    $routes->get('reports/aging', 'Api\\ReportsController::aging');
    $routes->get('reports/stock-balance', 'Api\\ReportsController::stockBalance');
    $routes->post('taxes/regional/rules', 'Api\\RegionalTaxesController::setRule');
    $routes->post('taxes/regional/preview', 'Api\\RegionalTaxesController::preview');
    $routes->post('taxes/regional/e-invoice', 'Api\\RegionalTaxesController::einvoice');
    $routes->post('taxes/withholding-certificates', 'Api\\WithholdingCertificatesController::create');
    $routes->get('employees', 'Api\\EmployeesController::index');
    $routes->get('employees/(:num)', 'Api\\EmployeesController::show/$1');
    $routes->post('employees', 'Api\\EmployeesController::create');
    $routes->put('employees/(:num)', 'Api\\EmployeesController::update/$1');
    $routes->get('leaves', 'Api\\LeavesController::index');
    $routes->post('leaves/apply', 'Api\\LeavesController::apply');
    $routes->post('leaves/(:num)/approve', 'Api\\LeavesController::approve/$1');
    $routes->get('leaves/balance', 'Api\\LeavesController::balance');
    $routes->get('attendances', 'Api\\AttendancesController::index');
    $routes->post('attendances', 'Api\\AttendancesController::create');
    $routes->post('payroll', 'Api\\PayrollController::create');
    $routes->get('payroll/(:num)', 'Api\\PayrollController::show/$1');
    $routes->get('salary-slips/(:num)', 'Api\\SalarySlipsController::show/$1');
    $routes->get('assets', 'Api\\AssetsController::index');
    $routes->get('assets/(:num)', 'Api\\AssetsController::show/$1');
    $routes->post('assets', 'Api\\AssetsController::create');
    $routes->post('assets/(:num)/capitalize', 'Api\\AssetsController::capitalize/$1');
    $routes->post('assets/(:num)/dispose', 'Api\\AssetsController::dispose/$1');
    $routes->post('assets/(:num)/status', 'Api\\AssetsController::changeStatus/$1');
    $routes->post('depreciation-schedules', 'Api\\DepreciationController::createSchedule');
    $routes->post('depreciation-schedules/(:num)/post/(:num)', 'Api\\DepreciationController::postLine/$1/$2');
    $routes->post('maintenance-schedules', 'Api\\MaintenanceController::createSchedule');
    $routes->post('maintenance-work-orders', 'Api\\MaintenanceController::createWorkOrder');
    $routes->post('maintenance-work-orders/(:num)/complete', 'Api\\MaintenanceController::completeWorkOrder/$1');
    $routes->get('projects', 'Api\\ProjectsController::index');
    $routes->get('projects/(:num)', 'Api\\ProjectsController::show/$1');
    $routes->post('projects', 'Api\\ProjectsController::create');
    $routes->put('projects/(:num)', 'Api\\ProjectsController::update/$1');
    $routes->get('projects/(:num)/timesheets/summary', 'Api\\ProjectsController::timesheetSummary/$1');
    $routes->post('tasks', 'Api\\TasksController::create');
    $routes->get('tasks/(:num)', 'Api\\TasksController::show/$1');
    $routes->post('tasks/(:num)/status', 'Api\\TasksController::updateStatus/$1');
    $routes->get('timesheets', 'Api\\TimesheetsController::index');
    $routes->get('timesheets/(:num)', 'Api\\TimesheetsController::show/$1');
    $routes->post('timesheets', 'Api\\TimesheetsController::create');
    $routes->post('timesheets/(:num)/submit', 'Api\\TimesheetsController::submit/$1');
    $routes->get('reorder-levels', 'Api\\ReorderPlanningController::reorderLevels');
    $routes->post('reorder-levels', 'Api\\ReorderPlanningController::createLevel');
    $routes->put('reorder-levels/(:num)', 'Api\\ReorderPlanningController::updateLevel/$1');
    $routes->delete('reorder-levels/(:num)', 'Api\\ReorderPlanningController::deleteLevel/$1');
    $routes->post('purchase-suggestions/generate', 'Api\\ReorderPlanningController::generateSuggestions');
    $routes->get('purchase-suggestions', 'Api\\ReorderPlanningController::listSuggestions');
    $routes->post('purchase-suggestions/(:num)/ack', 'Api\\ReorderPlanningController::acknowledge/$1');
    $routes->post('purchase-suggestions/(:num)/convert', 'Api\\ReorderPlanningController::convert/$1');
    $routes->get('order-templates', 'Api\\OrderTemplatesController::index');
    $routes->get('order-templates/(:num)', 'Api\\OrderTemplatesController::show/$1');
    $routes->post('order-templates', 'Api\\OrderTemplatesController::create');
    $routes->put('order-templates/(:num)', 'Api\\OrderTemplatesController::update/$1');
    $routes->delete('order-templates/(:num)', 'Api\\OrderTemplatesController::delete/$1');
    $routes->post('order-templates/(:num)/apply', 'Api\\OrderTemplatesController::apply/$1');
    $routes->get('order-subscriptions', 'Api\\OrderSubscriptionsController::index');
    $routes->post('order-subscriptions', 'Api\\OrderSubscriptionsController::create');
    $routes->put('order-subscriptions/(:num)', 'Api\\OrderSubscriptionsController::update/$1');
    $routes->post('order-subscriptions/run', 'Api\\OrderSubscriptionsController::run');
    $routes->get('boms', 'Api\\BOMsController::index');
    $routes->get('boms/(:num)', 'Api\\BOMsController::show/$1');
    $routes->post('boms', 'Api\\BOMsController::create');
    $routes->put('boms/(:num)', 'Api\\BOMsController::update/$1');
    $routes->delete('boms/(:num)', 'Api\\BOMsController::delete/$1');
    $routes->get('work-orders', 'Api\\WorkOrdersController::index');
    $routes->get('work-orders/(:num)', 'Api\\WorkOrdersController::show/$1');
    $routes->post('work-orders', 'Api\\WorkOrdersController::create');
    $routes->post('work-orders/(:num)/release', 'Api\\WorkOrdersController::release/$1');
    $routes->post('work-orders/(:num)/start', 'Api\\WorkOrdersController::start/$1');
    $routes->post('work-orders/(:num)/complete', 'Api\\WorkOrdersController::complete/$1');
    $routes->post('work-orders/(:num)/cancel', 'Api\\WorkOrdersController::cancel/$1');
    $routes->get('subscriptions', 'Api\\SubscriptionsController::index');
    $routes->get('subscriptions/(:num)', 'Api\\SubscriptionsController::show/$1');
    $routes->post('subscriptions', 'Api\\SubscriptionsController::create');
    $routes->put('subscriptions/(:num)', 'Api\\SubscriptionsController::update/$1');
    $routes->post('subscriptions/(:num)/pause', 'Api\\SubscriptionsController::pause/$1');
    $routes->post('subscriptions/(:num)/resume', 'Api\\SubscriptionsController::resume/$1');
    $routes->post('subscriptions/(:num)/cancel', 'Api\\SubscriptionsController::cancel/$1');
    $routes->post('subscriptions/run', 'Api\\SubscriptionsController::run');
    $routes->group('webhooks/ecommerce', ['filter' => 'webhookauth'], static function ($routes) {
        $routes->post('product', 'Api\\EcommerceWebhooksController::product');
        $routes->post('order', 'Api\\EcommerceWebhooksController::order');
    });
    $routes->get('quality-parameters', 'Api\\QualityInspectionsController::parameters');
    $routes->post('quality-parameters', 'Api\\QualityInspectionsController::createParameter');
    $routes->put('quality-parameters/(:num)', 'Api\\QualityInspectionsController::updateParameter/$1');
    $routes->delete('quality-parameters/(:num)', 'Api\\QualityInspectionsController::deleteParameter/$1');
    $routes->get('quality-inspections', 'Api\\QualityInspectionsController::index');
    $routes->get('quality-inspections/(:num)', 'Api\\QualityInspectionsController::show/$1');
    $routes->post('quality-inspections', 'Api\\QualityInspectionsController::create');
    $routes->post('quality-inspections/(:num)/submit', 'Api\\QualityInspectionsController::submit/$1');
    $routes->post('quality-inspections/(:num)/approve', 'Api\\QualityInspectionsController::approve/$1');
    $routes->post('quality-inspections/(:num)/reject', 'Api\\QualityInspectionsController::reject/$1');

    $routes->get('product-categories', 'Api\\ProductCategoriesController::index');
    $routes->get('product-categories/(:num)', 'Api\\ProductCategoriesController::show/$1');
    $routes->get('users', 'Api\\UsersController::index');
    $routes->get('users/me', 'Api\\UsersController::me');
    $routes->get('users/roles', 'Api\\UsersController::roles');
    $routes->get('users/branches', 'Api\\UsersController::branches');
    $routes->get('users/(:num)', 'Api\\UsersController::show/$1');
    $routes->post('users/create', 'Api\\UsersController::create');
    $routes->put('users/update/(:num)', 'Api\\UsersController::update/$1');
    $routes->delete('users/delete/(:num)', 'Api\\UsersController::delete/$1');
    $routes->put('users/(:num)/change-password', 'Api\\UsersController::changePassword/$1');

    // Customers
    $routes->get('customers', 'Api\\CustomersController::index');
    $routes->get('customers/export', 'Api\\CustomersController::export');
    $routes->post('customers/import', 'Api\\CustomersController::import');
    $routes->post('customers', 'Api\\CustomersController::create');
    // Customer Addresses (nested under customers, must be before (:num) alone)
    $routes->get('customers/(:num)/addresses', 'Api\\CustomerAddressesController::index/$1');
    $routes->post('customers/(:num)/addresses', 'Api\\CustomerAddressesController::create/$1');
    $routes->put('customers/(:num)/addresses/(:num)', 'Api\\CustomerAddressesController::update/$1/$2');
    $routes->delete('customers/(:num)/addresses/(:num)', 'Api\\CustomerAddressesController::delete/$1/$2');
    // Customer Debt Operations (nested under customers)
    $routes->get('customers/(:num)/debts', 'Api\\CustomerDebtController::index/$1');
    $routes->post('customers/(:num)/debts/payment', 'Api\\CustomerDebtController::payment/$1');
    $routes->post('customers/(:num)/debts/adjust', 'Api\\CustomerDebtController::adjust/$1');
    $routes->post('customers/(:num)/debts/discount', 'Api\\CustomerDebtController::discount/$1');
    // Customers CRUD (single customer by id)
    $routes->get('customers/(:num)', 'Api\\CustomersController::show/$1');
    $routes->put('customers/(:num)', 'Api\\CustomersController::update/$1');
    $routes->delete('customers/(:num)', 'Api\\CustomersController::delete/$1');
    
    // Customer Debt Transaction by code (for viewing/editing receipts)
    $routes->get('customer-debts/(:any)', 'Api\\CustomerDebtController::show/$1');
    $routes->put('customer-debts/(:any)', 'Api\\CustomerDebtController::update/$1');
    $routes->delete('customer-debts/(:any)', 'Api\\CustomerDebtController::delete/$1');
    
    // Customer Groups
    $routes->get('customer-groups', 'Api\\CustomerGroupsController::index');
    $routes->get('customer-groups/(:num)', 'Api\\CustomerGroupsController::show/$1');
    $routes->post('customer-groups', 'Api\\CustomerGroupsController::create');
    $routes->put('customer-groups/(:num)', 'Api\\CustomerGroupsController::update/$1');
    $routes->delete('customer-groups/(:num)', 'Api\\CustomerGroupsController::delete/$1');
    
    // Suppliers (Partners with type=supplier)
    $routes->get('suppliers', 'Api\\SuppliersController::index');
    $routes->get('suppliers/export', 'Api\\SuppliersController::export');
    $routes->get('suppliers/import-template', 'Api\\SuppliersController::importTemplate');
    $routes->post('suppliers/import', 'Api\\SuppliersController::import');
    $routes->get('suppliers/(:num)', 'Api\\SuppliersController::show/$1');
    $routes->post('suppliers', 'Api\\SuppliersController::create');
    $routes->put('suppliers/(:num)', 'Api\\SuppliersController::update/$1');
    $routes->delete('suppliers/(:num)', 'Api\\SuppliersController::delete/$1');
    // Supplier debt operations
    $routes->post('suppliers/(:num)/adjust', 'Api\\SuppliersController::adjust/$1');
    $routes->post('suppliers/(:num)/payment', 'Api\\SuppliersController::payment/$1');
    $routes->post('suppliers/(:num)/discount', 'Api\\SuppliersController::discount/$1');
    $routes->get('suppliers/(:num)/debt-history', 'Api\\SuppliersController::debtHistory/$1');
    $routes->get('suppliers/(:num)/export-receipts', 'Api\\SuppliersController::exportReceipts/$1');
    $routes->get('suppliers/(:num)/export-payables', 'Api\\SuppliersController::exportPayables/$1');
    $routes->get('variants', 'Api\\ProductVariantsController::deletedList');
    $routes->get('variants/deleted', 'Api\\ProductVariantsController::deletedList');
    $routes->get('variants/(:num)', 'Api\\ProductVariantsController::show/$1');
    $routes->put('variants/(:num)', 'Api\\ProductVariantsController::update/$1');
    $routes->delete('variants/(:num)', 'Api\\ProductVariantsController::delete/$1');
    $routes->put('variants/(:num)/restore', 'Api\\ProductVariantsController::restore/$1');
    $routes->delete('variants/(:num)/hard', 'Api\\ProductVariantsController::hardDelete/$1');
    $routes->post('variants/(:num)/upload-multiple', 'Api\\ProductVariantsController::uploadMultiple/$1');
    $routes->post('variants/(:num)/images/attach-multiple', 'Api\\ProductVariantsController::attachImages/$1');
    $routes->get('variants/(:num)/attribute-values', 'Api\\ProductVariantsController::attributeValues/$1');
    $routes->post('variants/(:num)/attribute-values/sync', 'Api\\ProductVariantsController::syncAttributeValues/$1');
    $routes->delete('attributes/remove-from-variant/(:num)/(:num)', 'Api\\ProductVariantsController::removeAttributeFromVariant/$1/$2');

    $routes->get('attributes', 'Api\\AttributesController::index');
    $routes->get('attributes/(:num)', 'Api\\AttributesController::show/$1');
    $routes->post('attributes', 'Api\\AttributesController::create');
    $routes->put('attributes/(:num)', 'Api\\AttributesController::update/$1');
    $routes->delete('attributes/(:num)', 'Api\\AttributesController::delete/$1');
    $routes->get('attributes/(:num)/options', 'Api\\AttributesController::options/$1');
    $routes->get('attributes/(:num)/products', 'Api\\AttributesController::productsByAttribute/$1');
    $routes->post('attributes/(:num)/options', 'Api\\AttributesController::createOption/$1');
    $routes->put('attributes/options/(:num)', 'Api\\AttributesController::updateOption/$1');
    $routes->delete('attributes/options/(:num)', 'Api\\AttributesController::deleteOption/$1');
    $routes->get('attributes/options/(:num)/products', 'Api\\AttributesController::productsByOption/$1');
    $routes->post('attribute-values', 'Api\\AttributesController::createValue');
    $routes->delete('attribute-values/(:num)', 'Api\\AttributesController::deleteValue/$1');

    $routes->get('roles', 'Api\\RolesController::index');
    $routes->get('roles/(:num)', 'Api\\RolesController::show/$1');
    $routes->post('roles/create', 'Api\\RolesController::create');
    $routes->put('roles/update/(:num)', 'Api\\RolesController::update/$1');
    $routes->delete('roles/delete/(:num)', 'Api\\RolesController::delete/$1');
    $routes->get('roles/(:num)/permissions', 'Api\\RolesController::getPermissions/$1');
    $routes->post('roles/(:num)/assign-permissions', 'Api\\RolesController::assignPermissions/$1');
    $routes->get('permissions', 'Api\\PermissionsController::index');

    $routes->get('branches', 'Api\\BranchesController::index');
    $routes->post('branches', 'Api\\BranchesController::create');
    $routes->get('branches/export', 'Api\\BranchesController::export');

    // Locations (Vietnam administrative divisions)
    $routes->get('locations/provinces', 'Api\\LocationsController::provinces');
    $routes->get('locations/provinces/(:num)/districts', 'Api\\LocationsController::districts/$1');
    $routes->get('locations/districts/(:num)/wards', 'Api\\LocationsController::wards/$1');
    $routes->get('locations/stats', 'Api\\LocationsController::stats');

    // Price lists
    $routes->get('price-lists', 'Api\\PriceListsController::index');
    $routes->get('price-lists/(:num)', 'Api\\PriceListsController::show/$1');
    $routes->post('price-lists', 'Api\\PriceListsController::create');
    $routes->put('price-lists/(:num)', 'Api\\PriceListsController::update/$1');
    $routes->delete('price-lists/(:num)', 'Api\\PriceListsController::delete/$1');
    $routes->get('price-lists/(:num)/items', 'Api\\PriceListsController::items/$1');
    $routes->post('price-lists/(:num)/items', 'Api\\PriceListsController::saveItems/$1');
    $routes->post('price-lists/(:num)/add-items', 'Api\\PriceListsController::addItems/$1');
    $routes->delete('price-lists/(:num)/items/(:num)', 'Api\\PriceListsController::removeItem/$1/$2');
    $routes->get('price-lists/(:num)/export', 'Api\\PriceListsController::export/$1');
    $routes->post('price-lists/(:num)/import', 'Api\\PriceListsController::import/$1');
    $routes->post('price-lists/(:num)/apply-formula', 'Api\\PriceListsController::applyFormula/$1');

    // Orders
    $routes->get('orders', 'Api\\OrdersController::index');
    $routes->get('orders/(:num)', 'Api\\OrdersController::show/$1');
    $routes->post('orders/calculate-preview', 'Api\\OrdersController::calculatePreview');
    $routes->post('orders', 'Api\\OrdersController::create');
    $routes->patch('orders/(:num)/status', 'Api\\OrderStatusController::update/$1');
    $routes->post('orders/(:num)/cancel', 'Api\\OrderCancellationController::cancel/$1');

    // POS
    $routes->post('pos/profiles', 'Api\\POSProfilesController::create');
    $routes->put('pos/profiles/(:num)', 'Api\\POSProfilesController::update/$1');
    $routes->get('pos/profiles/(:num)', 'Api\\POSProfilesController::show/$1');
    $routes->get('pos/profiles/resolve', 'Api\\POSProfilesController::resolve');
    $routes->post('pos/shifts/open', 'Api\\POSShiftsController::open');
    $routes->post('pos/shifts/close', 'Api\\POSShiftsController::close');
    $routes->get('pos/shifts/current', 'Api\\POSShiftsController::current');
    $routes->post('pos/offline/queue', 'Api\\POSOfflineController::queue');
    $routes->post('pos/offline/sync', 'Api\\POSOfflineController::sync');
    $routes->post('pos/coupons/apply', 'Api\\POSLoyaltyController::applyCoupon');
    $routes->post('pos/loyalty/redeem-preview', 'Api\\POSLoyaltyController::redeemPreview');
    // POS Sales - quick return, daily report, sellers
    $routes->post('pos/quick-return', 'Api\\POSSalesController::quickReturn');
    $routes->get('pos/daily-report', 'Api\\POSSalesController::dailyReport');
    $routes->get('pos/sellers', 'Api\\POSSalesController::sellers');

    // Bank Accounts
    $routes->get('bank-accounts', 'Api\\BankAccountsController::index');
    $routes->get('bank-accounts/(:num)', 'Api\\BankAccountsController::show/$1');
    $routes->post('bank-accounts', 'Api\\BankAccountsController::create');
    $routes->put('bank-accounts/(:num)', 'Api\\BankAccountsController::update/$1');
    $routes->delete('bank-accounts/(:num)', 'Api\\BankAccountsController::delete/$1');
    $routes->get('bank-accounts/(:num)/qr', 'Api\\BankAccountsController::qr/$1');

    // Sales Channels
    $routes->get('sales-channels', 'Api\\SalesChannelsController::index');
    $routes->get('sales-channels/(:num)', 'Api\\SalesChannelsController::show/$1');
    $routes->post('sales-channels', 'Api\\SalesChannelsController::create');
    $routes->put('sales-channels/(:num)', 'Api\\SalesChannelsController::update/$1');
    $routes->delete('sales-channels/(:num)', 'Api\\SalesChannelsController::delete/$1');

    // Shipping
    $routes->post('shipping/calculate', 'Api\\ShippingController::calculate');
    $routes->get('shipping/zones', 'Api\\ShippingController::zones');
    $routes->post('shipping/zones', 'Api\\ShippingController::createZone');
    $routes->put('shipping/zones/(:num)', 'Api\\ShippingController::updateZone/$1');
    $routes->delete('shipping/zones/(:num)', 'Api\\ShippingController::deleteZone/$1');
    $routes->get('shipping/zones/(:num)/rates', 'Api\\ShippingController::rates/$1');
    $routes->post('shipping/rates', 'Api\\ShippingController::createRate');
    $routes->put('shipping/rates/(:num)', 'Api\\ShippingController::updateRate/$1');
    $routes->delete('shipping/rates/(:num)', 'Api\\ShippingController::deleteRate/$1');

    // Shipments (delivery tracking view from invoices)
    $routes->get('shipments', 'Api\\ShipmentsController::index');
    $routes->get('shipments/(:num)', 'Api\\ShipmentsController::show/$1');

    // Coupons
    $routes->get('coupons', 'Api\\CouponsController::index');
    $routes->get('coupons/(:num)', 'Api\\CouponsController::show/$1');
    $routes->post('coupons', 'Api\\CouponsController::create');
    $routes->put('coupons/(:num)', 'Api\\CouponsController::update/$1');
    $routes->delete('coupons/(:num)', 'Api\\CouponsController::delete/$1');
    $routes->post('coupons/apply', 'Api\\CouponsController::apply');

    // Loyalty
    $routes->get('loyalty/wallet/(:num)', 'Api\\LoyaltyController::wallet/$1');
    $routes->post('loyalty/calculate-earn', 'Api\\LoyaltyController::calculateEarn');
    $routes->post('loyalty/redeem-preview', 'Api\\LoyaltyController::redeemPreview');
    $routes->post('loyalty/earn', 'Api\\LoyaltyController::earn');
    $routes->post('loyalty/redeem', 'Api\\LoyaltyController::redeem');
    $routes->get('loyalty/transactions/(:num)', 'Api\\LoyaltyController::transactions/$1');

    $routes->get('tax-templates', 'Api\\TaxTemplatesController::index');
    $routes->post('tax-templates', 'Api\\TaxTemplatesController::create');
    $routes->post('payment-entries', 'Api\\PaymentEntriesController::create');
    // CRM lead -> opportunity -> quotation
    $routes->post('leads', 'Api\\LeadsController::create');
    $routes->post('leads/(:num)/convert', 'Api\\LeadsController::convert/$1');
    $routes->post('opportunities', 'Api\\OpportunitiesController::create');
    $routes->post('opportunities/(:num)/stage', 'Api\\OpportunitiesController::updateStage/$1');
    $routes->post('quotations', 'Api\\QuotationsController::create');
    $routes->post('opportunities/(:num)/quote', 'Api\\QuotationsController::createFromOpportunity/$1');
    $routes->post('campaigns', 'Api\\CampaignsController::create');
    $routes->post('campaigns/(:num)/members', 'Api\\CampaignsController::addMember/$1');
    $routes->post('email-campaigns', 'Api\\EmailCampaignsController::create');
    $routes->post('email-campaigns/(:num)/status', 'Api\\EmailCampaignsController::updateStatus/$1');
    $routes->post('exchange-rates', 'Api\\ExchangeRatesController::create');
    $routes->get('chart-of-accounts', 'Api\\ChartOfAccountsController::index');
    $routes->get('chart-of-accounts/(:num)', 'Api\\ChartOfAccountsController::show/$1');
    $routes->get('chart-of-accounts/(:num)/children', 'Api\\ChartOfAccountsController::children/$1');
    $routes->post('chart-of-accounts', 'Api\\ChartOfAccountsController::create');
    $routes->get('tax-templates/(:num)', 'Api\\TaxTemplatesController::show/$1');
    $routes->post('gl/journal', 'Api\\GLEntriesController::postJournal');
    $routes->get('gl', 'Api\\GLEntriesController::index');
    $routes->get('aging', 'Api\\AgingReportsController::index');
    $routes->post('sales-invoices/preview', 'Api\\SalesInvoicesController::preview');
    $routes->post('sales-invoices', 'Api\\SalesInvoicesController::create');
    $routes->get('sales-invoices/(:num)', 'Api\\SalesInvoicesController::show/$1');
    $routes->post('sales-invoices/(:num)/submit', 'Api\\SalesInvoicesController::submit/$1');
    $routes->post('sales-invoices/(:num)/cancel', 'Api\\SalesInvoicesController::cancel/$1');
    $routes->post('purchase-invoices/preview', 'Api\\PurchaseInvoicesController::preview');
    $routes->post('purchase-invoices', 'Api\\PurchaseInvoicesController::create');
    $routes->get('purchase-invoices/(:num)', 'Api\\PurchaseInvoicesController::show/$1');
    $routes->post('purchase-invoices/(:num)/submit', 'Api\\PurchaseInvoicesController::submit/$1');
    $routes->post('purchase-invoices/(:num)/cancel', 'Api\\PurchaseInvoicesController::cancel/$1');
    $routes->get('purchase-orders', 'Api\\PurchaseOrdersController::index');
    $routes->get('purchase-orders/export', 'Api\\PurchaseOrdersController::export');
    $routes->post('purchase-orders', 'Api\\PurchaseOrdersController::create');
    $routes->get('purchase-orders/(:num)', 'Api\\PurchaseOrdersController::show/$1');
    $routes->post('purchase-orders/(:num)/submit', 'Api\\PurchaseOrdersController::submit/$1');
    $routes->post('purchase-orders/(:num)/cancel', 'Api\\PurchaseOrdersController::cancel/$1');

    // Purchase Returns (Trả hàng nhập)
    $routes->get('purchase-returns', 'PurchaseReturnController::index');
    $routes->get('purchase-returns/export', 'PurchaseReturnController::export');
    $routes->get('purchase-returns/(:num)', 'PurchaseReturnController::show/$1');
    $routes->post('purchase-returns', 'PurchaseReturnController::create');
    $routes->put('purchase-returns/(:num)', 'PurchaseReturnController::update/$1');
    $routes->delete('purchase-returns/(:num)', 'PurchaseReturnController::delete/$1');
    $routes->post('purchase-returns/(:num)/status', 'PurchaseReturnController::updateStatus/$1');
    $routes->post('goods-receipts', 'Api\\GoodsReceiptsController::create');
    $routes->get('goods-receipts/(:num)', 'Api\\GoodsReceiptsController::show/$1');
    $routes->post('landed-costs', 'Api\\LandedCostsController::create');
    $routes->get('landed-costs/(:num)', 'Api\\LandedCostsController::show/$1');
    $routes->post('subcontracting', 'Api\\SubcontractingController::create');
    $routes->post('subcontracting/(:num)/issue', 'Api\\SubcontractingController::issue/$1');
    $routes->post('subcontracting/(:num)/receive', 'Api\\SubcontractingController::receive/$1');
    $routes->post('payment-entries', 'Api\\PaymentEntriesController::create');
    $routes->get('payment-entries/(:num)', 'Api\\PaymentEntriesController::show/$1');
    $routes->post('payment-entries/(:num)/submit', 'Api\\PaymentEntriesController::submit/$1');
    $routes->post('payment-entries/(:num)/cancel', 'Api\\PaymentEntriesController::cancel/$1');
    $routes->post('bank-statements/import', 'Api\\BankReconciliationsController::import');
    $routes->post('bank-reconciliations/(:num)/match', 'Api\\BankReconciliationsController::match/$1');
    $routes->post('withholding-rules', 'Api\\WithholdingRulesController::create');
    $routes->get('withholding-rules/(:num)/apply', 'Api\\WithholdingRulesController::apply/$1');
    $routes->post('credit-limits', 'Api\\CreditLimitsController::upsert');
    $routes->post('credit-check', 'Api\\CreditLimitsController::check');
    $routes->post('support-tickets', 'Api\\SupportTicketsController::create');
    $routes->put('support-tickets/(:num)', 'Api\\SupportTicketsController::update/$1');
    $routes->get('support-tickets/(:num)', 'Api\\SupportTicketsController::show/$1');
    $routes->post('support-tickets/(:num)/status', 'Api\\SupportTicketsController::updateStatus/$1');
    $routes->post('support-tickets/(:num)/assign', 'Api\\SupportTicketsController::assign/$1');
    $routes->post('support-tickets/(:num)/communications', 'Api\\TicketCommunicationsController::create/$1');
    $routes->post('contract-templates', 'Api\\ContractTemplatesController::create');
    $routes->get('contract-templates/(:num)', 'Api\\ContractTemplatesController::show/$1');
    $routes->post('contracts', 'Api\\ContractsController::create');
    $routes->get('contracts/(:num)', 'Api\\ContractsController::show/$1');
    $routes->post('contracts/(:num)/activate', 'Api\\ContractsController::activate/$1');
    $routes->post('contracts/(:num)/close', 'Api\\ContractsController::close/$1');
    $routes->post('contracts/(:num)/renew', 'Api\\ContractsController::renew/$1');
    $routes->post('appointments', 'Api\\AppointmentsController::schedule');
    $routes->get('appointments/(:num)', 'Api\\AppointmentsController::show/$1');
    $routes->post('appointments/(:num)/reschedule', 'Api\\AppointmentsController::reschedule/$1');
    $routes->post('appointments/(:num)/cancel', 'Api\\AppointmentsController::cancel/$1');

    // Payment methods
    $routes->get('payment-methods', 'Api\\PaymentMethodsController::index');
    $routes->get('payment-methods/(:num)', 'Api\\PaymentMethodsController::show/$1');
    $routes->post('payment-methods', 'Api\\PaymentMethodsController::create');
    $routes->put('payment-methods/(:num)', 'Api\\PaymentMethodsController::update/$1');
    $routes->delete('payment-methods/(:num)', 'Api\\PaymentMethodsController::delete/$1');
    $routes->patch('payment-methods/(:num)/activate', 'Api\\PaymentMethodsController::activate/$1');
    $routes->patch('payment-methods/(:num)/deactivate', 'Api\\PaymentMethodsController::deactivate/$1');

    // Invoices
    $routes->get('invoices', 'Api\\InvoicesController::index');
    $routes->get('invoices/(:num)', 'Api\\InvoicesController::show/$1');
    $routes->post('invoices', 'Api\\InvoicesController::create');
    $routes->post('invoices/generate', 'Api\\InvoicesController::generate');
    $routes->post('invoices/(:num)/pdf', 'Api\\InvoicesController::generatePdf/$1');
    $routes->put('invoices/(:num)', 'Api\\InvoicesController::update/$1');
    $routes->post('invoices/(:num)/cancel', 'Api\\InvoicesController::cancel/$1');
    $routes->delete('invoices/(:num)', 'Api\\InvoicesController::delete/$1');

    // Returns
    $routes->get('returns', 'Api\\ReturnsController::index');
    $routes->get('returns/(:num)', 'Api\\ReturnsController::show/$1');
    $routes->post('returns', 'Api\\ReturnsController::create');
    $routes->patch('returns/(:num)/approve', 'Api\\ReturnsController::approve/$1');
    $routes->patch('returns/(:num)/reject', 'Api\\ReturnsController::reject/$1');
    $routes->patch('returns/(:num)/complete', 'Api\\ReturnsController::complete/$1');

    // Cash Transactions
    $routes->post('cash/receipt', 'Api\\CashTransactionsController::createReceipt');
    $routes->post('cash/payment', 'Api\\CashTransactionsController::createPayment');
    $routes->get('cash/transactions', 'Api\\CashTransactionsController::list');
    $routes->get('cash/transactions/(:num)', 'Api\\CashTransactionsController::show/$1');
    $routes->get('cash/balance', 'Api\\CashTransactionsController::getBalance');
    $routes->get('cash/balance/branch/(:num)', 'Api\\CashTransactionsController::getBranchBalance/$1');
    $routes->get('cash/report/daily', 'Api\\CashTransactionsController::dailyReport');
    $routes->delete('cash/transactions/(:num)', 'Api\\CashTransactionsController::delete/$1');

    // Shipping COD settlement
    $routes->post('shipping/settlement', 'Api\\ShippingController::settleCOD');

    // Delivery Partners (self-delivery)
    $routes->get('delivery-partners', 'Api\\DeliveryPartnersController::index');
    $routes->get('delivery-partners/(:num)', 'Api\\DeliveryPartnersController::show/$1');
    $routes->post('delivery-partners', 'Api\\DeliveryPartnersController::create');
    $routes->put('delivery-partners/(:num)', 'Api\\DeliveryPartnersController::update/$1');
    $routes->delete('delivery-partners/(:num)', 'Api\\DeliveryPartnersController::delete/$1');

    // Purchase order receive -> auto cash payment
    $routes->post('purchase-orders/(:num)/receive', 'Api\\PurchaseOrdersController::receive/$1');

    // Order payments (partial/multi-method)
    $routes->post('orders/(:num)/payments', 'Api\\OrderPaymentsController::create/$1');

    // Webhooks
    $routes->get('webhooks/subscriptions', 'Api\\WebhookSubscriptionsController::index');
    $routes->post('webhooks/subscriptions', 'Api\\WebhookSubscriptionsController::create');
    $routes->put('webhooks/subscriptions/(:num)', 'Api\\WebhookSubscriptionsController::update/$1');
    $routes->patch('webhooks/subscriptions/(:num)/activate', 'Api\\WebhookSubscriptionsController::activate/$1');
    $routes->patch('webhooks/subscriptions/(:num)/deactivate', 'Api\\WebhookSubscriptionsController::deactivate/$1');
    $routes->get('webhook-events', 'Api\\WebhookEventsController::index');
    $routes->post('webhook-events/(:num)/retry', 'Api\\WebhookEventsController::retry/$1');
});

// Debug
// $routes->get('debug-fk', 'DebugFKController::index');
// $routes->get('debug-fix', 'DebugFKController::fix');

// Catch-all for frontend build
$routes->get('.*', 'Home::index');
