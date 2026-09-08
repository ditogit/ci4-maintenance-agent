<?php

namespace MaintenanceAgent\Controllers\Api;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

class BaseController extends Controller
{
    protected function success(array $data, int $code = 200, ?string $requestId = null): ResponseInterface
    {
        $requestId ??= $this->request->getHeaderLine('X-Request-ID') ?: 'mnt_' . bin2hex(random_bytes(6));

        return $this->response
            ->setStatusCode($code)
            ->setHeader('X-Request-ID', $requestId)
            ->setJSON([
                'success'    => true,
                'data'       => $data,
                'request_id' => $requestId,
            ]);
    }

    protected function fail(string $code, string $message, int $httpCode = 400, array $extra = []): ResponseInterface
    {
        $requestId = $this->request->getHeaderLine('X-Request-ID') ?: 'mnt_' . bin2hex(random_bytes(6));
        $body = [
            'success'    => false,
            'error'      => ['code' => $code, 'message' => $message],
            'request_id' => $requestId,
        ];

        if ($extra !== []) {
            $body['data'] = $extra;
        }

        return $this->response
            ->setStatusCode($httpCode)
            ->setHeader('X-Request-ID', $requestId)
            ->setJSON($body);
    }
}
