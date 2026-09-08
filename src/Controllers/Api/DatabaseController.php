<?php

namespace MaintenanceAgent\Controllers\Api;

use MaintenanceAgent\Services\DatabaseService;

class DatabaseController extends BaseController
{
    public function index()
    {
        $service = new DatabaseService();

        return $this->success($service->getStats());
    }
}
