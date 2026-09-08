<?php

namespace MaintenanceAgent\Controllers\Api;

use MaintenanceAgent\Services\StorageService;

class StorageController extends BaseController
{
    public function index()
    {
        $service = new StorageService();

        return $this->success($service->getStats());
    }
}
