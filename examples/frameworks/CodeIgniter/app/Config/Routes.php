<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// API Routes with AI Rate Limiting
$routes->group('api', ['filter' => 'ai-rate-limit'], static function ($routes) {
    
    // User management routes
    $routes->group('v1/users', static function ($routes) {
        $routes->get('/', 'UserController::index');
        $routes->post('/', 'UserController::create');
        $routes->get('(:num)', 'UserController::show/$1');
        $routes->put('(:num)', 'UserController::update/$1');
        $routes->delete('(:num)', 'UserController::delete/$1');
    });
    
    // Post management routes
    $routes->group('v1/posts', static function ($routes) {
        $routes->get('/', 'PostController::index');
        $routes->post('/', 'PostController::create');
        $routes->get('(:num)', 'PostController::show/$1');
        $routes->put('(:num)', 'PostController::update/$1');
        $routes->delete('(:num)', 'PostController::delete/$1');
    });
    
    // Admin routes with different limits
    $routes->group('v1/admin', ['filter' => 'auth'], static function ($routes) {
        $routes->get('analytics', 'AdminController::analytics');
        $routes->get('users', 'AdminController::users');
        $routes->get('stats', 'AdminController::stats');
    });
});

// Public routes (no rate limiting)
$routes->get('health', 'HealthController::index');
$routes->get('/', 'HomeController::index');

// Apply rate limiting to specific routes
$routes->get('api/v1/limited', 'LimitedController::index', ['filter' => 'ai-rate-limit']); 