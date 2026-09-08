<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('api/v1/maintenance', ['namespace' => 'MaintenanceAgent\Controllers\Api', 'filter' => 'maintenance'], static function (RouteCollection $routes): void {
    $routes->get('health', 'HealthController::index');
    $routes->get('info', 'InfoController::index');
    $routes->get('session/stats', 'SessionController::stats');
    $routes->post('session/cleanup', 'SessionController::cleanup');
    $routes->post('session/clear', 'SessionController::clear');
    $routes->get('database', 'DatabaseController::index');
    $routes->get('storage', 'StorageController::index');
});
