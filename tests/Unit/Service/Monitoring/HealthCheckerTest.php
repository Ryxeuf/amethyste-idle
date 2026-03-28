<?php

namespace App\Tests\Unit\Service\Monitoring;

use App\Service\Monitoring\HealthChecker;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Cache\CacheInterface;

class HealthCheckerTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private CacheInterface&MockObject $cache;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
    }

    private function createChecker(string $mercureUrl = 'http://localhost/.well-known/mercure'): HealthChecker
    {
        return new HealthChecker($this->em, $this->cache, $mercureUrl);
    }

    public function testCheckReturnsCorrectStructure(): void
    {
        $this->em->method('getConnection')
            ->willThrowException(new \RuntimeException('No DB'));
        $this->cache->method('get')->willReturn('pong');

        $result = $this->createChecker()->check();

        $this->assertArrayHasKey('status', $result);
        $this->assertArrayHasKey('checks', $result);
        $this->assertArrayHasKey('database', $result['checks']);
        $this->assertArrayHasKey('cache', $result['checks']);
        $this->assertArrayHasKey('mercure', $result['checks']);
        $this->assertArrayHasKey('status', $result['checks']['database']);
        $this->assertArrayHasKey('status', $result['checks']['cache']);
        $this->assertArrayHasKey('status', $result['checks']['mercure']);
    }

    public function testDegradedWhenDatabaseFails(): void
    {
        $this->em->method('getConnection')
            ->willThrowException(new \RuntimeException('Connection refused'));
        $this->cache->method('get')->willReturn('pong');

        $result = $this->createChecker()->check();

        $this->assertSame('degraded', $result['status']);
        $this->assertSame('error', $result['checks']['database']['status']);
        $this->assertSame('ok', $result['checks']['cache']['status']);
        $this->assertSame('ok', $result['checks']['mercure']['status']);
    }

    public function testDegradedWhenCacheFails(): void
    {
        $this->em->method('getConnection')
            ->willThrowException(new \RuntimeException('No DB'));
        $this->cache->method('get')
            ->willThrowException(new \RuntimeException('Cache failure'));

        $result = $this->createChecker()->check();

        $this->assertSame('degraded', $result['status']);
        $this->assertSame('error', $result['checks']['cache']['status']);
    }

    public function testDegradedWhenMercureUrlEmpty(): void
    {
        $this->em->method('getConnection')
            ->willThrowException(new \RuntimeException('No DB'));
        $this->cache->method('get')->willReturn('pong');

        $result = $this->createChecker('')->check();

        $this->assertSame('degraded', $result['status']);
        $this->assertSame('error', $result['checks']['mercure']['status']);
    }

    public function testCacheOkWhenWorking(): void
    {
        $this->em->method('getConnection')
            ->willThrowException(new \RuntimeException('No DB'));
        $this->cache->method('get')->willReturn('pong');

        $result = $this->createChecker()->check();

        $this->assertSame('ok', $result['checks']['cache']['status']);
        $this->assertArrayHasKey('latency_ms', $result['checks']['cache']);
        $this->assertIsFloat($result['checks']['cache']['latency_ms']);
    }
}
