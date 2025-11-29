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
    $routes->get('customers/(:num)', 'Api\\CustomersController::show/$1');
    $routes->post('customers', 'Api\\CustomersController::create');
    $routes->put('customers/(:num)', 'Api\\CustomersController::update/$1');
    $routes->get('customers/export', 'Api\\CustomersController::export');
    $routes->post('customers/import', 'Api\\CustomersController::import');
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

    // Price lists
    $routes->get('price-lists', 'Api\\PriceListsController::index');
    $routes->get('price-lists/(:num)', 'Api\\PriceListsController::show/$1');
    $routes->post('price-lists', 'Api\\PriceListsController::create');
    $routes->put('price-lists/(:num)', 'Api\\PriceListsController::update/$1');
    $routes->delete('price-lists/(:num)', 'Api\\PriceListsController::delete/$1');
    $routes->get('price-lists/(:num)/items', 'Api\\PriceListsController::items/$1');
    $routes->post('price-lists/(:num)/items', 'Api\\PriceListsController::saveItems/$1');

    // Orders
    $routes->get('orders', 'Api\\OrdersController::index');
    $routes->get('orders/(:num)', 'Api\\OrdersController::show/$1');
    $routes->post('orders/calculate-preview', 'Api\\OrdersController::calculatePreview');
    $routes->post('orders', 'Api\\OrdersController::create');
    $routes->patch('orders/(:num)/status', 'Api\\OrderStatusController::update/$1');
    $routes->post('orders/(:num)/cancel', 'Api\\OrderCancellationController::cancel/$1');

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

// Catch-all for frontend build
$routes->get('.*', 'Home::index');
