<?php

namespace MaintenanceAgent\Controllers\Api;

use MaintenanceAgent\Services\InfoService;

class InfoController extends BaseController
{
    public function index()
    {
        $service = new InfoService();

        return $this->success($service->getInfo());
    }
}
