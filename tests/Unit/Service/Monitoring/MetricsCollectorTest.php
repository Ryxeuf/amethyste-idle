<?php

namespace App\Tests\Unit\Service\Monitoring;

use App\Service\Monitoring\MetricsCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class MetricsCollectorTest extends TestCase
{
    private MetricsCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new MetricsCollector(new ArrayAdapter());
    }

    public function testIncrementCounter(): void
    {
        $this->collector->incrementCounter('http_requests_total', 1.0, 'method="GET"');
        $this->collector->incrementCounter('http_requests_total', 1.0, 'method="GET"');

        $metrics = $this->collector->getAll();
        $this->assertSame(2.0, $metrics['counter:http_requests_total:method="GET"']);
    }

    public function testSetGauge(): void
    {
        $this->collector->setGauge('players_online', 42.0);
        $metrics = $this->collector->getAll();
        $this->assertSame(42.0, $metrics['gauge:players_online:']);

        $this->collector->setGauge('players_online', 10.0);
        $metrics = $this->collector->getAll();
        $this->assertSame(10.0, $metrics['gauge:players_online:']);
    }

    public function testObserveHistogram(): void
    {
        $this->collector->observeHistogram('http_request_duration_seconds', 0.05);
        $this->collector->observeHistogram('http_request_duration_seconds', 0.5);

        $metrics = $this->collector->getAll();
        $this->assertSame(0.55, $metrics['histogram_sum:http_request_duration_seconds:']);
        $this->assertSame(2.0, $metrics['histogram_count:http_request_duration_seconds:']);

        // 0.05 fits in bucket 0.05, 0.1, 0.25, 0.5, 1.0, 2.0, 5.0, 10.0
        $this->assertSame(1.0, $metrics['histogram_bucket:http_request_duration_seconds::le=0.05']);
        // 0.5 fits in bucket 0.5, 1.0, 2.0, 5.0, 10.0 — so both values fit in 0.5
        $this->assertSame(2.0, $metrics['histogram_bucket:http_request_duration_seconds::le=0.5']);
        // +Inf always has all
        $this->assertSame(2.0, $metrics['histogram_bucket:http_request_duration_seconds::le=+Inf']);
    }

    public function testRenderPrometheus(): void
    {
        $this->collector->incrementCounter('http_requests_total', 3.0, 'method="GET"');
        $this->collector->setGauge('players_online', 5.0);

        $output = $this->collector->renderPrometheus();

        $this->assertStringContainsString('# TYPE amethyste_http_requests_total counter', $output);
        $this->assertStringContainsString('amethyste_http_requests_total{method="GET"} 3', $output);
        $this->assertStringContainsString('# TYPE amethyste_players_online gauge', $output);
        $this->assertStringContainsString('amethyste_players_online 5', $output);
    }

    public function testEmptyMetrics(): void
    {
        $output = $this->collector->renderPrometheus();
        $this->assertSame("\n", $output);
    }
}
