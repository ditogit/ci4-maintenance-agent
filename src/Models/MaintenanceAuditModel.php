<?php

namespace MaintenanceAgent\Models;

use CodeIgniter\Model;

class MaintenanceAuditModel extends Model
{
    protected $table          = 'maintenance_audit_logs';
    protected $primaryKey     = 'id';
    protected $allowedFields  = ['request_id', 'action', 'status', 'affected_rows', 'ip_address', 'metadata', 'created_at'];
    protected $useTimestamps  = false;
    protected $returnType     = 'array';

    public function log(string $action, string $status, int $affectedRows = 0, array $metadata = []): bool
    {
        try {
            $requestId = bin2hex(random_bytes(6));
            if (function_exists('service') && isset($_SERVER['HTTP_X_REQUEST_ID'])) {
                $requestId = $_SERVER['HTTP_X_REQUEST_ID'];
            }

            return (bool) $this->insert([
                'request_id'    => $requestId,
                'action'        => $action,
                'status'        => $status,
                'affected_rows' => $affectedRows,
                'ip_address'    => $_SERVER['REMOTE_ADDR'] ?? $metadata['ip'] ?? 'unknown',
                'metadata'      => json_encode($metadata),
                'created_at'    => date('Y-m-d H:i:s'),
            ], false);
        } catch (\Throwable $e) {
            log_message('error', '[MaintenanceAgent] audit log failed: ' . $e->getMessage());

            return false;
        }
    }
}
