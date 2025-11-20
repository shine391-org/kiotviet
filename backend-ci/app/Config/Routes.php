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
});

// Catch-all for frontend build
$routes->get('.*', 'Home::index');
