<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventListener;

use App\EventListener\ApiCorsSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ApiCorsSubscriberTest extends TestCase
{
    public function testPreflightIsAnsweredForAllowedOrigin(): void
    {
        $subscriber = new ApiCorsSubscriber('https://app.example');

        $request = Request::create('/api/v1/ping', 'OPTIONS');
        $request->headers->set('Origin', 'https://app.example');
        $event = $this->requestEvent($request);

        $subscriber->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertNotNull($response);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('https://app.example', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('Authorization', (string) $response->headers->get('Access-Control-Allow-Headers'));
    }

    public function testPreflightIsIgnoredForDisallowedOrigin(): void
    {
        $subscriber = new ApiCorsSubscriber('https://app.example');

        $request = Request::create('/api/v1/ping', 'OPTIONS');
        $request->headers->set('Origin', 'https://evil.example');
        $event = $this->requestEvent($request);

        $subscriber->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testResponseHeadersAddedForAllowedOrigin(): void
    {
        $subscriber = new ApiCorsSubscriber('https://app.example,capacitor://localhost');

        $request = Request::create('/api/v1/fight', 'GET');
        $request->headers->set('Origin', 'capacitor://localhost');
        $event = $this->responseEvent($request, $response = new Response());

        $subscriber->onKernelResponse($event);

        $this->assertSame('capacitor://localhost', $response->headers->get('Access-Control-Allow-Origin'));
        $this->assertStringContainsString('Origin', (string) $response->headers->get('Vary'));
        $this->assertNull($response->headers->get('Access-Control-Allow-Credentials'));
    }

    public function testWildcardOrigin(): void
    {
        $subscriber = new ApiCorsSubscriber('*');

        $request = Request::create('/api/v1/ping', 'GET');
        $request->headers->set('Origin', 'https://anything.example');
        $event = $this->responseEvent($request, $response = new Response());

        $subscriber->onKernelResponse($event);

        $this->assertSame('*', $response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testInactiveWhenNoOriginsConfigured(): void
    {
        $subscriber = new ApiCorsSubscriber(null);

        $request = Request::create('/api/v1/ping', 'GET');
        $request->headers->set('Origin', 'https://app.example');
        $event = $this->responseEvent($request, $response = new Response());

        $subscriber->onKernelResponse($event);

        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    public function testNonApiPathIsIgnored(): void
    {
        $subscriber = new ApiCorsSubscriber('https://app.example');

        $request = Request::create('/game/zone', 'GET');
        $request->headers->set('Origin', 'https://app.example');
        $event = $this->responseEvent($request, $response = new Response());

        $subscriber->onKernelResponse($event);

        $this->assertNull($response->headers->get('Access-Control-Allow-Origin'));
    }

    private function requestEvent(Request $request): RequestEvent
    {
        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }

    private function responseEvent(Request $request, Response $response): ResponseEvent
    {
        return new ResponseEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST, $response);
    }
}
