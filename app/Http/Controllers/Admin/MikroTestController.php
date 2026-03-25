<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditLogService;
use App\Services\Mikro\MikroClient;
use App\Services\Mikro\MikroException;
use Illuminate\Http\JsonResponse;

class MikroTestController extends Controller
{
    public function __construct(
        private MikroClient $mikro,
        private AuditLogService $audit
    ) {}

    public function __invoke(): JsonResponse
    {
        try {
            $this->mikro->testConnection();
            $this->audit->log('mikro.test.success');

            return response()->json([
                'success' => true,
                'status' => 'ok',
            ]);
        } catch (MikroException $exception) {
            $this->audit->log('mikro.test.failed', null, [
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'status' => 'fail',
            ], 503);
        }
    }
}
