<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->group('api/v1/maintenance', ['namespace' => 'MaintenanceAgent\Controllers\Api'], static function (RouteCollection $routes): void {
    $routes->get('health', 'HealthController::index', ['filter' => 'maintenanceEnabled,maintenanceAuth,maintenanceRateLimit']);
    $routes->get('info', 'InfoController::index', ['filter' => 'maintenanceEnabled,maintenanceAuth,maintenanceRateLimit']);
    $routes->get('session/stats', 'SessionController::stats', ['filter' => 'maintenanceEnabled,maintenanceAuth,maintenanceRateLimit']);
    $routes->post('session/cleanup', 'SessionController::cleanup', ['filter' => 'maintenanceEnabled,maintenanceAuth,maintenanceRateLimit']);
    $routes->post('session/clear', 'SessionController::clear', ['filter' => 'maintenanceEnabled,maintenanceAuth,maintenanceRateLimit']);
    $routes->get('database', 'DatabaseController::index', ['filter' => 'maintenanceEnabled,maintenanceAuth,maintenanceRateLimit']);
    $routes->get('storage', 'StorageController::index', ['filter' => 'maintenanceEnabled,maintenanceAuth,maintenanceRateLimit']);
});
