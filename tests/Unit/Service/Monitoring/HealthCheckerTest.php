<?php

namespace App\Tests\Unit\Service\Monitoring;

use App\Service\Monitoring\HealthChecker;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Contracts\Cache\CacheInterface;

class HealthCheckerTest extends TestCase
{
    private Connection&MockObject $connection;
    private HubInterface&MockObject $mercureHub;
    private CacheInterface&MockObject $cache;
    private HealthChecker $checker;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(Connection::class);
        $this->mercureHub = $this->createMock(HubInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->checker = new HealthChecker(
            $this->connection,
            $this->mercureHub,
            $this->cache,
        );
    }

    public function testHealthyWhenAllChecksPass(): void
    {
        $this->connection->method('executeQuery')->willReturn($this->createMock(\Doctrine\DBAL\Result::class));
        $this->cache->method('get')->willReturn('pong');
        $this->mercureHub->method('getUrl')->willReturn('https://mercure.example.com');

        $result = $this->checker->check();

        $this->assertSame('healthy', $result['status']);
        $this->assertSame('ok', $result['checks']['database']['status']);
        $this->assertSame('ok', $result['checks']['cache']['status']);
        $this->assertSame('ok', $result['checks']['mercure']['status']);
    }

    public function testDegradedWhenDatabaseFails(): void
    {
        $this->connection->method('executeQuery')->willThrowException(new \RuntimeException('Connection refused'));
        $this->cache->method('get')->willReturn('pong');
        $this->mercureHub->method('getUrl')->willReturn('https://mercure.example.com');

        $result = $this->checker->check();

        $this->assertSame('degraded', $result['status']);
        $this->assertSame('error', $result['checks']['database']['status']);
        $this->assertSame('ok', $result['checks']['cache']['status']);
    }

    public function testDegradedWhenCacheFails(): void
    {
        $this->connection->method('executeQuery')->willReturn($this->createMock(\Doctrine\DBAL\Result::class));
        $this->cache->method('get')->willThrowException(new \RuntimeException('Cache failure'));
        $this->mercureHub->method('getUrl')->willReturn('https://mercure.example.com');

        $result = $this->checker->check();

        $this->assertSame('degraded', $result['status']);
        $this->assertSame('ok', $result['checks']['database']['status']);
        $this->assertSame('error', $result['checks']['cache']['status']);
    }

    public function testDatabaseLatencyIsReported(): void
    {
        $this->connection->method('executeQuery')->willReturn($this->createMock(\Doctrine\DBAL\Result::class));
        $this->cache->method('get')->willReturn('pong');
        $this->mercureHub->method('getUrl')->willReturn('https://mercure.example.com');

        $result = $this->checker->check();

        $this->assertArrayHasKey('latency_ms', $result['checks']['database']);
        $this->assertIsFloat($result['checks']['database']['latency_ms']);
    }
}
