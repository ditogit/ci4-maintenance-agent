<?php

namespace MaintenanceAgent\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use MaintenanceAgent\Config\Maintenance;
use MaintenanceAgent\Security\NonceService;
use MaintenanceAgent\Security\SignatureService;
use MaintenanceAgent\Security\TimestampValidator;

class MaintenanceAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $config = config(Maintenance::class);

        $ipAllowlist = $config->getAllowedIps();
        if ($ipAllowlist !== []) {
            $ip = $request->getIPAddress();
            if (! in_array($ip, $ipAllowlist, true)) {
                return $this->error(403, 'FORBIDDEN', 'IP not allowed');
            }
        }

        $apiKey    = $request->getHeaderLine('X-Maintenance-Key');
        $timestamp = $request->getHeaderLine('X-Maintenance-Timestamp');
        $nonce     = $request->getHeaderLine('X-Maintenance-Nonce');
        $signature = $request->getHeaderLine('X-Maintenance-Signature');

        if ($apiKey === '' || $timestamp === '' || $signature === '') {
            $this->audit('auth_failure', 'missing_headers', $request);

            return $this->error(401, 'UNAUTHORIZED', 'Missing authentication headers');
        }

        if ($apiKey !== $config->apiKey) {
            $this->audit('auth_failure', 'invalid_key', $request);

            return $this->error(401, 'UNAUTHORIZED', 'Invalid API key');
        }

        if (TimestampValidator::isExpired($timestamp, $config->clockSkew)) {
            return $this->error(401, 'TIMESTAMP_EXPIRED', 'Timestamp expired or clock skew too large');
        }

        if ($nonce !== '' && NonceService::isReplay($nonce)) {
            return $this->error(401, 'UNAUTHORIZED', 'Replay detected (nonce reuse)');
        }

        $method = $request->getMethod();
        $path   = '/' . ltrim($request->getPath(), '/');
        $body   = (string) $request->getBody();

        $payload = SignatureService::buildPayload($timestamp, $nonce, $method, $path, $body);

        if (! SignatureService::verify($payload, $config->apiSecret, $signature)) {
            $this->audit('auth_failure', 'invalid_signature', $request);

            return $this->error(401, 'INVALID_SIGNATURE', 'Invalid signature');
        }

        if (! $this->hasPermission($request, $config)) {
            return $this->error(403, 'FORBIDDEN', 'Insufficient permission');
        }

        $requestId = 'mnt_' . bin2hex(random_bytes(8));
        $request->setHeader('X-Request-ID', $requestId);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $reqId = $request->getHeaderLine('X-Request-ID');
        if ($reqId !== '') {
            $response->setHeader('X-Request-ID', $reqId);
        }

        return null;
    }

    private function hasPermission(RequestInterface $request, Maintenance $config): bool
    {
        $path   = trim($request->getPath(), '/');
        $method = strtoupper($request->getMethod());

        $map = [
            'api/v1/maintenance/health'         => 'maintenance.health.read',
            'api/v1/maintenance/info'           => 'maintenance.info.read',
            'api/v1/maintenance/session/stats'  => 'maintenance.session.read',
            'api/v1/maintenance/session/cleanup' => 'maintenance.session.cleanup',
            'api/v1/maintenance/session/clear'  => 'maintenance.session.clear',
            'api/v1/maintenance/database'       => 'maintenance.database.read',
            'api/v1/maintenance/storage'        => 'maintenance.storage.read',
            'api/v1/maintenance/cache/clear'    => 'maintenance.cache.clear',
            'api/v1/maintenance/logs'           => 'maintenance.logs.read',
            'api/v1/maintenance/logs/clear'     => 'maintenance.logs.clear',
            'api/v1/maintenance/backup'         => 'maintenance.backup.create',
            'api/v1/maintenance/queue'          => 'maintenance.queue.read',
            'api/v1/maintenance/queue/retry'    => 'maintenance.queue.retry',
            'api/v1/maintenance/queue/clear'    => 'maintenance.queue.clear',
        ];

        $permission = $map[$path] ?? null;

        if ($permission === null) {
            return true;
        }

        if ($permission === 'maintenance.session.clear' && ! $config->sessionClear) {
            return false;
        }

        if ($permission === 'maintenance.session.cleanup' && ! $config->sessionCleanup) {
            return false;
        }

        if ($permission === 'maintenance.session.read' && ! $config->sessionStats) {
            return false;
        }

        if ($permission === 'maintenance.database.read' && ! $config->databaseStats) {
            return false;
        }

        if ($permission === 'maintenance.storage.read' && ! $config->storageStats) {
            return false;
        }

        if ($permission === 'maintenance.cache.clear' && ! $config->cacheClear) {
            return false;
        }

        if (in_array($permission, ['maintenance.logs.read', 'maintenance.logs.clear'], true) && ! $config->logViewer) {
            return false;
        }

        if ($permission === 'maintenance.backup.create' && ! $config->backup) {
            return false;
        }

        if (in_array($permission, ['maintenance.queue.read', 'maintenance.queue.retry', 'maintenance.queue.clear'], true) && ! $config->queue) {
            return false;
        }

        return in_array($permission, $config->permissions, true);
    }

    private function error(int $code, string $errCode, string $message)
    {
        $this->audit('auth_failure', strtolower($errCode), Services::request());

        return Services::response()
            ->setStatusCode($code)
            ->setJSON([
                'success' => false,
                'error'   => ['code' => $errCode, 'message' => $message],
            ]);
    }

    private function audit(string $action, string $status, RequestInterface $request): void
    {
        try {
            $model = new \MaintenanceAgent\Models\MaintenanceAuditModel();
            $model->log($action, $status, 0, ['ip' => $request->getIPAddress(), 'path' => $request->getPath()]);
        } catch (\Throwable $e) {
            log_message('error', '[MaintenanceAgent] audit failed: ' . $e->getMessage());
        }
    }
}
