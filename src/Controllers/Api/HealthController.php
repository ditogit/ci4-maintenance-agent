<?php

namespace MaintenanceAgent\Controllers\Api;

use MaintenanceAgent\Services\HealthService;

class HealthController extends BaseController
{
    public function index()
    {
        $service = new HealthService();
        $data    = $service->check();

        return $this->success($data);
    }
}
