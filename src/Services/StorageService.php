<?php

namespace MaintenanceAgent\Services;

class StorageService
{
    public function getStats(): array
    {
        $path = FCPATH ?? ROOTPATH ?? __DIR__;

        $total = @disk_total_space($path);
        $free  = @disk_free_space($path);

        if ($total === false || $free === false || $total <= 0) {
            return [
                'status'       => 'unsupported',
                'total_gb'     => null,
                'used_gb'      => null,
                'free_gb'      => null,
                'used_percent' => null,
            ];
        }

        $used = $total - $free;

        return [
            'status'       => 'healthy',
            'total_gb'     => round($total / 1024 / 1024 / 1024, 2),
            'used_gb'      => round($used / 1024 / 1024 / 1024, 2),
            'free_gb'      => round($free / 1024 / 1024 / 1024, 2),
            'used_percent' => $total > 0 ? (int) round(($used / $total) * 100) : 0,
        ];
    }
}
