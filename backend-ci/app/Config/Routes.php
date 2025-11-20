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
    $routes->get('health', 'Api\\HealthController::index');
    $routes->get('products', 'Api\\ProductsController::index');
    $routes->get('products/(:num)', 'Api\\ProductsController::show/$1');
    $routes->get('products/(:num)/variants', 'Api\\ProductsController::variants/$1');
    $routes->get('product-categories', 'Api\\ProductCategoriesController::index');
    $routes->get('product-categories/tree', 'Api\\ProductCategoriesController::tree');
    $routes->get('users', 'Api\\UsersController::index');
    $routes->get('users/me', 'Api\\UsersController::me');
    $routes->get('users/roles', 'Api\\UsersController::roles');
    $routes->get('users/branches', 'Api\\UsersController::branches');
});

// Catch-all for frontend build
$routes->get('.*', 'Home::index');
