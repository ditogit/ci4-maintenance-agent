<?php

namespace MaintenanceAgent\Controllers\Api;

use MaintenanceAgent\Services\CacheService;

class CacheController extends BaseController
{
    public function clear()
    {
        $body = $this->request->getJSON(true);
        if (!is_array($body)) {
            $body = $this->request->getPost() ?? [];
        }
        $type = $body['type'] ?? $this->request->getVar('type') ?? 'all';
        $type = is_string($type) ? strtolower(trim($type)) : 'all';
        if (!in_array($type, ['all', 'default', 'file', 'config', 'route'], true)) {
            $type = 'all';
        }

        $service = new CacheService();

        try {
            $result = $service->clear($type);
        } catch (\RuntimeException $e) {
            $http = $e->getCode() === 403 ? 403 : ($e->getCode() === 409 ? 409 : 500);
            $code = $e->getCode() === 403 ? 'FEATURE_DISABLED' : ($e->getCode() === 409 ? 'MAINTENANCE_IN_PROGRESS' : 'MAINTENANCE_ERROR');
            return $this->fail($code, $e->getMessage(), $http);
        }

        $this->audit('cache_clear', 'success', 1);

        return $this->success($result);
    }

    private function audit(string $action, string $status, int $rows): void
    {
        try {
            $m = new \MaintenanceAgent\Models\MaintenanceAuditModel();
            $m->log($action, $status, $rows, ['ip' => $this->request->getIPAddress()]);
        } catch (\Throwable $e) {
        }
    }
}
