<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/', 'Home::index');

$routes->group('api/v1', function($routes) {
    // $routes->get('register-user', 'UserController::index');
    $routes->get('register-user/validate/(:num)', 'UserController::register_user/$1', ['filter' => 'auth']);
    $routes->get('retrieve-borrowed-books/(:num)', 'UserController::retrieveUserBorrowedBooks/$1', ['filter' => 'auth']);
    $routes->post('register-user', 'UserController::register_user', ['filter' => 'auth']);
    $routes->get('get-book', 'BookshelfController::getBook', ['filter' => 'auth']);
    $routes->post('borrow-book', 'BookshelfController::borrowBook', ['filter' => 'auth']);
    $routes->post('return-book', 'BookshelfController::returnBooks', ['filter' => 'auth']);
});

$routes->group('api/v1/auth',['namespace' => 'App\Controllers\Admin'], function($routes) {
    $routes->post('create-admin', 'AdminController::createAdmin');
    $routes->post('login', 'AdminController::login');
    $routes->post('logout', 'AdminController::logout');
});

$routes->post('update-db', 'AutoUpdateDBController::auto_update_days_fee');

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}
