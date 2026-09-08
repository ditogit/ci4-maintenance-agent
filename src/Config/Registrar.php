<?php

namespace MaintenanceAgent\Config;

class Registrar
{
    public static function Filters(): array
    {
        return [
            'aliases' => [
                'maintenance'          => \MaintenanceAgent\Filters\MaintenanceFilter::class,
                'maintenanceEnabled'   => \MaintenanceAgent\Filters\MaintenanceEnabledFilter::class,
                'maintenanceAuth'      => \MaintenanceAgent\Filters\MaintenanceAuthFilter::class,
                'maintenanceRateLimit' => \MaintenanceAgent\Filters\MaintenanceRateLimitFilter::class,
            ],
            'globals' => [
                'before' => [],
                'after'  => [],
            ],
        ];
    }
}
