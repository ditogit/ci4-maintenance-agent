<?php

namespace MaintenanceAgent\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $enabled = new MaintenanceEnabledFilter();
        if ($res = $enabled->before($request, $arguments)) {
            return $res;
        }

        $rate = new MaintenanceRateLimitFilter();
        if ($res = $rate->before($request, $arguments)) {
            return $res;
        }

        $auth = new MaintenanceAuthFilter();
        return $auth->before($request, $arguments);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $auth = new MaintenanceAuthFilter();
        return $auth->after($request, $response, $arguments);
    }
}
