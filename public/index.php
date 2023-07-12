<?php

/**
 * Front controller
 *
 * PHP version 7.0
 */


ini_set('session.cookie_lifetime', '864000'); // ten days in seconds


/**
 * Composer
 */
require dirname(__DIR__) . '/vendor/autoload.php';


/**
 * Error and Exception handling
 */
error_reporting(E_ALL);
set_error_handler('Core\Error::errorHandler');
set_exception_handler('Core\Error::exceptionHandler');

/**
* Sessions
*/
session_start();


/**
 * Routing
 */
$router = new Core\Router();

// Add the routes
$router->add('explore', ['controller' => 'Home', 'action' => 'index']);
$router->add('', ['controller' => 'Panel', 'action' => 'index']);
$router->add('login', ['controller' => 'Login', 'action' => 'new']);
$router->add('logout', ['controller' => 'Login', 'action' => 'destroy']);
$router->add('search', ['controller' => 'Search', 'action' => 'index']);
$router->add('list', ['controller' => 'ListContent', 'action' => 'index']);
$router->add('user/{username:\w+}', ['controller' => 'Social', 'action' => 'index']);
$router->add('content/{kind:\w+}/{id:\d+}', ['controller' => 'Post', 'action' => 'index']);
$router->add('password/reset/{token:[\da-f]+}', ['controller' => 'Password', 'action' => 'reset']);
$router->add('signup/activate/{token:[\da-f]+}', ['controller' => 'Signup', 'action' => 'activate']);
$router->add('Profile/confirmation/{token:[\s\S]+}', ['controller' => 'Profile', 'action' => 'confirmation']);
$router->add('{controller}/{action}');

$router->dispatch($_SERVER['QUERY_STRING']);
