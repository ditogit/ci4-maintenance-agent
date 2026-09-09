<?php

namespace MaintenanceAgent\Services;

use MaintenanceAgent\Config\Maintenance;

class QueueService
{
    public function getStats(): array
    {
        $config = config(Maintenance::class);
        if (!$config->queue) {
            return [
                'queue_enabled' => false,
                'driver'        => null,
                'pending'       => 0,
                'failed'        => 0,
            ];
        }

        $queueEnabled = $this->detectQueueEnabled();
        if (!$queueEnabled) {
            return [
                'queue_enabled' => false,
                'driver'        => null,
                'pending'       => 0,
                'failed'        => 0,
            ];
        }

        $pending = 0;
        $failed = 0;
        $driver = 'database';

        try {
            $queueCfg = null;
            if (class_exists(\Config\Queue::class)) {
                $queueCfg = config(\Config\Queue::class);
                $driver = $queueCfg->default ?? $queueCfg->driver ?? 'database';
            }
        } catch (\Throwable $e) {
        }

        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('jobs')) {
                $pending = (int) $db->table('jobs')->countAll();
            } elseif ($db->tableExists('queue_jobs')) {
                $pending = (int) $db->table('queue_jobs')->countAll();
            }
            if ($db->tableExists('failed_jobs')) {
                $failed = (int) $db->table('failed_jobs')->countAll();
            } elseif ($db->tableExists('queue_failed_jobs')) {
                $failed = (int) $db->table('queue_failed_jobs')->countAll();
            }
        } catch (\Throwable $e) {
        }

        return [
            'queue_enabled' => true,
            'driver'        => $driver,
            'pending'       => $pending,
            'failed'        => $failed,
        ];
    }

    public function retryFailed(): array
    {
        $config = config(Maintenance::class);
        if (!$config->queue) {
            throw new \RuntimeException('Queue is disabled', 403);
        }

        $lockKey = 'maintenance_queue_retry_lock';
        $cache = cache();
        if ($cache->get($lockKey) !== null) {
            throw new \RuntimeException('Queue retry already in progress', 409);
        }
        $cache->save($lockKey, 1, 60);

        try {
            $retried = 0;
            try {
                $db = \Config\Database::connect();
                $failedTable = null;
                $jobsTable = null;

                if ($db->tableExists('failed_jobs')) {
                    $failedTable = 'failed_jobs';
                } elseif ($db->tableExists('queue_failed_jobs')) {
                    $failedTable = 'queue_failed_jobs';
                }

                if ($db->tableExists('jobs')) {
                    $jobsTable = 'jobs';
                } elseif ($db->tableExists('queue_jobs')) {
                    $jobsTable = 'queue_jobs';
                }

                if ($failedTable !== null && $jobsTable !== null) {
                    $failed = $db->table($failedTable)->get()->getResultArray();
                    foreach ($failed as $row) {
                        $payload = $row['payload'] ?? $row['job'] ?? json_encode($row);
                        $db->table($jobsTable)->insert([
                            'queue'       => $row['queue'] ?? 'default',
                            'payload'     => $payload,
                            'attempts'    => 0,
                            'reserved_at' => null,
                            'available_at' => time(),
                            'created_at'  => time(),
                        ]);
                        $retried++;
                    }
                    $db->table($failedTable)->truncate();
                } elseif ($failedTable !== null) {
                    $count = (int) $db->table($failedTable)->countAll();
                    $db->table($failedTable)->truncate();
                    $retried = $count;
                }
            } catch (\Throwable $e) {
                throw new \RuntimeException('Queue retry failed: ' . $e->getMessage(), 500);
            }

            try {
                $m = new \MaintenanceAgent\Models\MaintenanceAuditModel();
                $m->log('queue_retry', 'success', $retried, []);
            } catch (\Throwable $e) {
            }

            return ['retried' => $retried];
        } finally {
            $cache->delete($lockKey);
        }
    }

    public function clearFailed(): array
    {
        $config = config(Maintenance::class);
        if (!$config->queue) {
            throw new \RuntimeException('Queue is disabled', 403);
        }

        $lockKey = 'maintenance_queue_clear_lock';
        $cache = cache();
        if ($cache->get($lockKey) !== null) {
            throw new \RuntimeException('Queue clear already in progress', 409);
        }
        $cache->save($lockKey, 1, 60);

        try {
            $deleted = 0;
            try {
                $db = \Config\Database::connect();
                $tables = ['failed_jobs', 'queue_failed_jobs'];
                foreach ($tables as $table) {
                    if ($db->tableExists($table)) {
                        $count = (int) $db->table($table)->countAll();
                        $db->table($table)->truncate();
                        $deleted += $count;
                    }
                }
            } catch (\Throwable $e) {
                throw new \RuntimeException('Queue clear failed: ' . $e->getMessage(), 500);
            }

            try {
                $m = new \MaintenanceAgent\Models\MaintenanceAuditModel();
                $m->log('queue_clear', 'success', $deleted, []);
            } catch (\Throwable $e) {
            }

            return ['deleted' => $deleted];
        } finally {
            $cache->delete($lockKey);
        }
    }

    private function detectQueueEnabled(): bool
    {
        if (class_exists(\Config\Queue::class)) {
            return true;
        }

        try {
            $db = \Config\Database::connect();
            if ($db->tableExists('jobs') || $db->tableExists('queue_jobs') || $db->tableExists('failed_jobs') || $db->tableExists('queue_failed_jobs')) {
                return true;
            }
        } catch (\Throwable $e) {
        }

        return false;
    }
}
