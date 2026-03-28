<?php

namespace App\Tests\Unit\Service\Monitoring;

use App\Service\Monitoring\HealthChecker;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class HealthCheckerTest extends TestCase
{
    private Connection&MockObject $connection;
    private CacheInterface&MockObject $cache;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->cache = $this->createMock(CacheInterface::class);
    }

    private function createChecker(string $mercureUrl = 'http://localhost/.well-known/mercure'): HealthChecker
    {
        return new HealthChecker($this->connection, $this->cache, $mercureUrl);
    }

    public function testHealthyWhenAllChecksPass(): void
    {
        $this->connection->method('fetchOne')->willReturn(1);
        $this->cache->method('get')->willReturn('pong');

        $result = $this->createChecker()->check();

        $this->assertSame('healthy', $result['status']);
        $this->assertSame('ok', $result['checks']['database']['status']);
        $this->assertSame('ok', $result['checks']['cache']['status']);
        $this->assertSame('ok', $result['checks']['mercure']['status']);
    }

    public function testDegradedWhenDatabaseFails(): void
    {
        $this->connection->method('fetchOne')->willThrowException(new \RuntimeException('Connection refused'));
        $this->cache->method('get')->willReturn('pong');

        $result = $this->createChecker()->check();

        $this->assertSame('degraded', $result['status']);
        $this->assertSame('error', $result['checks']['database']['status']);
        $this->assertSame('ok', $result['checks']['cache']['status']);
    }

    public function testDegradedWhenCacheFails(): void
    {
        $this->connection->method('fetchOne')->willReturn(1);
        $this->cache->method('get')->willThrowException(new \RuntimeException('Cache failure'));

        $result = $this->createChecker()->check();

        $this->assertSame('degraded', $result['status']);
        $this->assertSame('ok', $result['checks']['database']['status']);
        $this->assertSame('error', $result['checks']['cache']['status']);
    }

    public function testDegradedWhenMercureUrlEmpty(): void
    {
        $this->connection->method('fetchOne')->willReturn(1);
        $this->cache->method('get')->willReturn('pong');

        $result = $this->createChecker('')->check();

        $this->assertSame('degraded', $result['status']);
        $this->assertSame('error', $result['checks']['mercure']['status']);
    }

    public function testDatabaseLatencyIsReported(): void
    {
        $this->connection->method('fetchOne')->willReturn(1);
        $this->cache->method('get')->willReturn('pong');

        $result = $this->createChecker()->check();

        $this->assertArrayHasKey('latency_ms', $result['checks']['database']);
        $this->assertIsFloat($result['checks']['database']['latency_ms']);
    }
}
