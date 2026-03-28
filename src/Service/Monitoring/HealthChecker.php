<?php

namespace App\Service\Monitoring;

use Doctrine\DBAL\Connection;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Contracts\Cache\CacheInterface;

class HealthChecker
{
    public function __construct(
        private readonly Connection $connection,
        private readonly HubInterface $mercureHub,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return array{status: string, checks: array<string, array{status: string, message?: string, latency_ms?: float}>}
     */
    public function check(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'mercure' => $this->checkMercure(),
        ];

        $allHealthy = true;
        foreach ($checks as $check) {
            if ($check['status'] !== 'ok') {
                $allHealthy = false;
                break;
            }
        }

        return [
            'status' => $allHealthy ? 'healthy' : 'degraded',
            'checks' => $checks,
        ];
    }

    /**
     * @return array{status: string, message?: string, latency_ms?: float}
     */
    private function checkDatabase(): array
    {
        $start = microtime(true);

        try {
            $this->connection->fetchOne('SELECT 1');
            $latency = (microtime(true) - $start) * 1000;

            return ['status' => 'ok', 'latency_ms' => round($latency, 2)];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Connection failed'];
        }
    }

    /**
     * @return array{status: string, message?: string, latency_ms?: float}
     */
    private function checkCache(): array
    {
        $start = microtime(true);

        try {
            $key = 'health_check_ping';
            $this->cache->get($key, function () {
                return 'pong';
            });
            $latency = (microtime(true) - $start) * 1000;

            return ['status' => 'ok', 'latency_ms' => round($latency, 2)];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Cache unavailable'];
        }
    }

    /**
     * @return array{status: string, message?: string}
     */
    private function checkMercure(): array
    {
        try {
            $this->mercureHub->getUrl();

            return ['status' => 'ok'];
        } catch (\Throwable $e) {
            return ['status' => 'error', 'message' => 'Hub unreachable'];
        }
    }
}
