<?php

namespace MaintenanceAgent\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use MaintenanceAgent\Config\Maintenance;

class MaintenanceRateLimitFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $config  = config(Maintenance::class);
        $throttler = Services::throttler();

        $path = trim($request->getPath(), '/');
        $ip   = $request->getIPAddress() ?? 'unknown';

        [$limit, $seconds, $keySuffix] = $this->resolveLimit($path, $config);

        $key = 'maintenance_' . $keySuffix . '_' . $ip;

        if (! $throttler->check($key, $limit, $seconds)) {
            return Services::response()
                ->setStatusCode(429)
                ->setJSON([
                    'success' => false,
                    'error'   => ['code' => 'RATE_LIMITED', 'message' => 'Too many requests'],
                ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function resolveLimit(string $path, Maintenance $config): array
    {
        if ($path === 'api/v1/maintenance/session/clear') {
            return [$config->rateLimitClear, 60, 'clear'];
        }

        if (in_array($path, ['api/v1/maintenance/session/cleanup'], true)) {
            return [$config->rateLimitMaintenance, 60, 'maintenance'];
        }

        return [$config->rateLimitRead, 60, 'read'];
    }
}
