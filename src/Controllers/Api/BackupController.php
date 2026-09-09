<?php

namespace MaintenanceAgent\Controllers\Api;

use MaintenanceAgent\Services\BackupService;

class BackupController extends BaseController
{
    public function create()
    {
        $service = new BackupService();

        try {
            $result = $service->createDatabase();
        } catch (\RuntimeException $e) {
            $http = $e->getCode() === 403 ? 403 : ($e->getCode() === 409 ? 409 : 500);
            $code = $e->getCode() === 403 ? 'FEATURE_DISABLED' : ($e->getCode() === 409 ? 'MAINTENANCE_IN_PROGRESS' : 'MAINTENANCE_ERROR');
            return $this->fail($code, $e->getMessage(), $http);
        }

        return $this->success($result);
    }
}
