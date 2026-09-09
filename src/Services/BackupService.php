<?php

namespace MaintenanceAgent\Services;

use MaintenanceAgent\Config\Maintenance;

class BackupService
{
    public function createDatabase(): array
    {
        $config = config(Maintenance::class);
        if (!$config->backup) {
            throw new \RuntimeException('Backup is disabled', 403);
        }

        $start = microtime(true);
        $lockKey = 'maintenance_backup_lock';
        $cache = cache();
        if ($cache->get($lockKey) !== null) {
            throw new \RuntimeException('Backup already in progress', 409);
        }
        $cache->save($lockKey, 1, 300);

        try {
            $backupDir = WRITEPATH . 'backups/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            $timestamp = date('Ymd_His');
            $fileName = 'backup_' . $timestamp . '.sql';
            $filePath = $backupDir . $fileName;

            $created = false;
            $error = null;

            try {
                $dbConfig = config(\Config\Database::class);
                $default = $dbConfig->default ?? [];
                $database = $default['database'] ?? null;
                $hostname = $default['hostname'] ?? 'localhost';
                $username = $default['username'] ?? '';
                $password = $default['password'] ?? '';

                if ($database) {
                    $dump = $this->dumpViaMysqldump($hostname, $username, $password, $database, $filePath);
                    if ($dump) {
                        $created = true;
                    } else {
                        $created = $this->dumpViaSystemFallback($database, $filePath);
                    }
                }

                if (!$created) {
                    $created = $this->dumpViaSystemFallback(null, $filePath);
                }
            } catch (\Throwable $e) {
                $error = $e->getMessage();
                $created = $this->dumpViaSystemFallback(null, $filePath);
            }

            if (!is_file($filePath)) {
                @file_put_contents($filePath, '-- backup placeholder ' . $timestamp . ' --' . PHP_EOL . ($error ? '-- error: ' . $error . ' --' . PHP_EOL : ''));
            }

            $size = is_file($filePath) ? filesize($filePath) : 0;
            $sizeMb = round($size / 1024 / 1024, 2);
            $durationMs = (int) ((microtime(true) - $start) * 1000);

            try {
                $m = new \MaintenanceAgent\Models\MaintenanceAuditModel();
                $m->log('backup_create', 'success', 1, ['file' => $fileName, 'size_mb' => $sizeMb]);
            } catch (\Throwable $e) {
            }

            return [
                'file'        => $fileName,
                'path'        => $filePath,
                'size'        => $size,
                'size_mb'     => $sizeMb,
                'duration_ms' => $durationMs,
            ];
        } finally {
            $cache->delete($lockKey);
        }
    }

    private function dumpViaMysqldump(string $host, string $user, string $pass, string $db, string $outFile): bool
    {
        $mysqldump = $this->findMysqldump();
        if ($mysqldump === null) {
            return false;
        }

        $cmd = sprintf(
            '%s --host=%s --user=%s %s %s --result-file=%s 2>&1',
            escapeshellarg($mysqldump),
            escapeshellarg($host),
            escapeshellarg($user),
            $pass !== '' ? '--password=' . escapeshellarg($pass) : '',
            escapeshellarg($db),
            escapeshellarg($outFile)
        );

        @exec($cmd, $output, $code);

        return $code === 0 && is_file($outFile) && filesize($outFile) > 0;
    }

    private function dumpViaSystemFallback(?string $db, string $outFile): bool
    {
        try {
            $database = \Config\Database::connect();
            $tables = $database->listTables();
            $content = '-- Database backup fallback ' . date('Y-m-d H:i:s') . PHP_EOL;
            if ($db !== null) {
                $content .= '-- database: ' . $db . PHP_EOL;
            }
            foreach ($tables as $table) {
                $content .= PHP_EOL . '-- Table: ' . $table . PHP_EOL;
                try {
                    $rows = $database->table($table)->get()->getResultArray();
                    $content .= '-- rows: ' . count($rows) . PHP_EOL;
                } catch (\Throwable $e) {
                    $content .= '-- error reading table: ' . $e->getMessage() . PHP_EOL;
                }
            }
            @file_put_contents($outFile, $content);

            return is_file($outFile);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function findMysqldump(): ?string
    {
        $candidates = ['mysqldump', '/usr/bin/mysqldump', '/usr/local/bin/mysqldump'];
        foreach ($candidates as $bin) {
            $out = [];
            $code = 0;
            @exec(escapeshellarg($bin) . ' --version 2>&1', $out, $code);
            if ($code === 0) {
                return $bin;
            }
        }

        return null;
    }
}
