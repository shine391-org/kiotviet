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
});

// Catch-all for frontend build
$routes->get('.*', 'Home::index');
