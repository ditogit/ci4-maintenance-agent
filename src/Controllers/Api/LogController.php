<?php

namespace MaintenanceAgent\Controllers\Api;

use MaintenanceAgent\Services\LogService;

class LogController extends BaseController
{
    public function index()
    {
        $channel = $this->request->getVar('channel') ?? $this->request->getGet('channel');
        $limit = $this->request->getVar('limit') ?? $this->request->getGet('limit') ?? 100;
        $limit = (int) $limit;

        $service = new LogService();

        try {
            $result = $service->tail($channel, $limit);
        } catch (\RuntimeException $e) {
            $http = $e->getCode() === 403 ? 403 : 500;
            $code = $e->getCode() === 403 ? 'FEATURE_DISABLED' : 'MAINTENANCE_ERROR';
            return $this->fail($code, $e->getMessage(), $http);
        }

        return $this->success($result);
    }

    public function clear()
    {
        $body = $this->request->getJSON(true);
        if (!is_array($body)) {
            $body = [];
        }
        $confirm = $body['confirm'] ?? $this->request->getVar('confirm') ?? $this->request->getPost('confirm') ?? '';

        $service = new LogService();

        try {
            $result = $service->clear((string) $confirm);
        } catch (\RuntimeException $e) {
            $code = $e->getCode();
            $http = $code === 403 ? 403 : ($code === 409 ? 409 : ($code === 422 ? 422 : 500));
            $err = $code === 403 ? 'FEATURE_DISABLED' : ($code === 409 ? 'MAINTENANCE_IN_PROGRESS' : ($code === 422 ? 'VALIDATION_ERROR' : 'MAINTENANCE_ERROR'));
            return $this->fail($err, $e->getMessage(), $http);
        }

        return $this->success($result);
    }
}
