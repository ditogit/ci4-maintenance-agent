<?php

namespace MaintenanceAgent\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use MaintenanceAgent\Config\Maintenance;

class MaintenanceEnabledFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $config = config(Maintenance::class);

        if (! $config->enabled) {
            return Services::response()
                ->setStatusCode(503)
                ->setJSON([
                    'success' => false,
                    'error'   => ['code' => 'AGENT_DISABLED', 'message' => 'Maintenance agent is disabled'],
                ]);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
