<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'app' => $this->checkApp(),
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $status = collect($checks)->every(fn ($check) => $check['status'] === 'ok') ? 'ok' : 'degraded';

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $status === 'ok' ? 200 : 503);
    }

    protected function checkApp(): array
    {
        return [
            'status' => 'ok',
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'environment' => config('app.env'),
        ];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['status' => 'ok'];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'message' => $exception->getMessage(),
            ];
        }
    }

    protected function checkCache(): array
    {
        $store = Config::get('cache.default');

        try {
            $cache = Cache::store($store);
            $cache->put('__health_check__', 'ok', 5);
            $value = $cache->get('__health_check__');

            return [
                'status' => $value === 'ok' ? 'ok' : 'error',
                'store' => $store,
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'store' => $store,
                'message' => $exception->getMessage(),
            ];
        }
    }

    protected function checkStorage(): array
    {
        $diskName = Config::get('filesystems.default', 'local');

        try {
            $disk = Storage::disk($diskName);
            $path = 'health/'.uniqid().'.tmp';
            $disk->put($path, 'ok');
            $disk->delete($path);

            return [
                'status' => 'ok',
                'disk' => $diskName,
            ];
        } catch (Throwable $exception) {
            return [
                'status' => 'error',
                'disk' => $diskName,
                'message' => $exception->getMessage(),
            ];
        }
    }

    protected function checkQueue(): array
    {
        return [
            'status' => 'ok',
            'driver' => Config::get('queue.default'),
        ];
    }
}
