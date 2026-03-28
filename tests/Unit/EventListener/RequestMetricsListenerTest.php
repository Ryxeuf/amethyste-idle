<?php

namespace App\Tests\Unit\EventListener;

use App\EventListener\RequestMetricsListener;
use App\Service\Monitoring\MetricsCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class RequestMetricsListenerTest extends TestCase
{
    private MetricsCollector $collector;
    private RequestMetricsListener $listener;

    protected function setUp(): void
    {
        $this->collector = new MetricsCollector(new ArrayAdapter());
        $this->listener = new RequestMetricsListener($this->collector);
    }

    public function testRequestResponseCycleRecordsMetrics(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/game/map', 'GET');

        $requestEvent = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $this->listener->onKernelRequest($requestEvent);

        $this->assertTrue($request->attributes->has('_metrics_start'));

        $response = new Response('', 200);
        $responseEvent = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $this->listener->onKernelResponse($responseEvent);

        $metrics = $this->collector->getAll();
        $this->assertSame(1.0, $metrics['counter:http_requests_total:method="GET",status="2xx"']);
    }

    public function testExcludedPathsAreNotTracked(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/metrics', 'GET');

        $requestEvent = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
        $this->listener->onKernelRequest($requestEvent);

        $response = new Response('', 200);
        $responseEvent = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);
        $this->listener->onKernelResponse($responseEvent);

        $metrics = $this->collector->getAll();
        $this->assertEmpty($metrics);
    }

    public function testSubRequestsAreIgnored(): void
    {
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = Request::create('/game/map', 'GET');

        $requestEvent = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);
        $this->listener->onKernelRequest($requestEvent);

        $this->assertFalse($request->attributes->has('_metrics_start'));
    }
}
