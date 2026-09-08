<?php

namespace MaintenanceAgent\Controllers\Api;

use MaintenanceAgent\Services\SessionService;

class SessionController extends BaseController
{
    public function stats()
    {
        $service = new SessionService();
        $data    = $service->getStats();

        return $this->success($data);
    }

    public function cleanup()
    {
        $body = $this->request->getJSON(true);
        $mode = $body['mode'] ?? 'expired';

        if ($mode !== 'expired') {
            return $this->fail('VALIDATION_ERROR', 'Only mode=expired is supported', 422);
        }

        $service = new SessionService();

        try {
            $result = $service->cleanupExpired();
        } catch (\RuntimeException $e) {
            $code = $e->getCode() === 409 ? 409 : 500;
            $err  = $e->getCode() === 409 ? 'MAINTENANCE_IN_PROGRESS' : 'MAINTENANCE_ERROR';

            return $this->fail($err, $e->getMessage(), $code);
        }

        if ($result['timed_out']) {
            $this->audit('session_cleanup', 'timeout', $result['deleted']);

            return $this->response
                ->setStatusCode(200)
                ->setJSON([
                    'success'    => false,
                    'error'      => ['code' => 'MAINTENANCE_TIMEOUT', 'message' => 'Cleanup timed out, partial delete completed'],
                    'data'       => ['deleted' => $result['deleted'], 'remaining' => $result['remaining'], 'duration_ms' => $result['duration_ms']],
                    'request_id' => $this->request->getHeaderLine('X-Request-ID'),
                ]);
        }

        $this->audit('session_cleanup', 'success', $result['deleted']);

        return $this->success([
            'deleted'     => $result['deleted'],
            'remaining'   => $result['remaining'],
            'duration_ms' => $result['duration_ms'],
        ]);
    }

    public function clear()
    {
        $service = new SessionService();

        try {
            $result = $service->clearAll();
        } catch (\RuntimeException $e) {
            $http = $e->getCode() === 403 ? 403 : ($e->getCode() === 409 ? 409 : 500);
            $code = $e->getCode() === 403 ? 'FEATURE_DISABLED' : ($e->getCode() === 409 ? 'MAINTENANCE_IN_PROGRESS' : 'MAINTENANCE_ERROR');

            return $this->fail($code, $e->getMessage(), $http);
        }

        $this->audit('session_clear', 'success', $result['deleted']);

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
