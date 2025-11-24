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
    $routes->get('products/(:num)/analytics', 'Api\\ProductsController::analytics/$1');
    $routes->get('products/media/library', 'Api\\ProductMediaController::library');
    $routes->get('products/media/by-date', 'Api\\ProductMediaController::byDate');
    $routes->get('products/media/search-sku', 'Api\\ProductMediaController::searchSku');

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
    $routes->post('roles/create', 'Api\\RolesController::create');
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
