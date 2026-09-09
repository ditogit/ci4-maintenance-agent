<?php

namespace MaintenanceAgent\Controllers\Api;

use MaintenanceAgent\Services\QueueService;

class QueueController extends BaseController
{
    public function index()
    {
        $service = new QueueService();
        $data = $service->getStats();

        return $this->success($data);
    }

    public function retry()
    {
        $service = new QueueService();

        try {
            $result = $service->retryFailed();
        } catch (\RuntimeException $e) {
            $http = $e->getCode() === 403 ? 403 : ($e->getCode() === 409 ? 409 : 500);
            $code = $e->getCode() === 403 ? 'FEATURE_DISABLED' : ($e->getCode() === 409 ? 'MAINTENANCE_IN_PROGRESS' : 'MAINTENANCE_ERROR');
            return $this->fail($code, $e->getMessage(), $http);
        }

        return $this->success($result);
    }

    public function clear()
    {
        $service = new QueueService();

        try {
            $result = $service->clearFailed();
        } catch (\RuntimeException $e) {
            $http = $e->getCode() === 403 ? 403 : ($e->getCode() === 409 ? 409 : 500);
            $code = $e->getCode() === 403 ? 'FEATURE_DISABLED' : ($e->getCode() === 409 ? 'MAINTENANCE_IN_PROGRESS' : 'MAINTENANCE_ERROR');
            return $this->fail($code, $e->getMessage(), $http);
        }

        return $this->success($result);
    }
}
