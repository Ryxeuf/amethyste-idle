<?php

namespace App\Tests\Functional\Controller;

use App\Controller\LocaleController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LocaleControllerTest extends TestCase
{
    private LocaleController $controller;
    private UrlGeneratorInterface $router;

    protected function setUp(): void
    {
        $this->controller = new LocaleController(['fr', 'en'], 'fr');

        $this->router = $this->createMock(UrlGeneratorInterface::class);
        $this->router->method('generate')
            ->with('app_home')
            ->willReturn('/');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id) => $id === 'router');
        $container->method('get')->willReturnCallback(fn (string $id) => $id === 'router' ? $this->router : null);

        $this->controller->setContainer($container);
    }

    public function testValidLocaleIsStoredInSession(): void
    {
        $request = $this->createRequestWithSession();

        $this->controller->changeLocale($request, 'en');

        $this->assertSame('en', $request->getSession()->get('_locale'));
    }

    public function testInvalidLocaleFallsBackToDefault(): void
    {
        $request = $this->createRequestWithSession();

        $this->controller->changeLocale($request, 'zz');

        $this->assertSame('fr', $request->getSession()->get('_locale'));
    }

    public function testMaliciousLocalePayloadIsRejected(): void
    {
        $request = $this->createRequestWithSession();

        $this->controller->changeLocale($request, '../../../etc/passwd');

        $this->assertSame('fr', $request->getSession()->get('_locale'));
    }

    public function testSafeRefererIsFollowed(): void
    {
        $request = $this->createRequestWithSession();
        $request->headers->set('referer', 'http://localhost/game/settings');

        $response = $this->controller->changeLocale($request, 'en');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('http://localhost/game/settings', $response->getTargetUrl());
    }

    public function testUnsafeRefererFallsBackToHome(): void
    {
        $request = $this->createRequestWithSession();
        $request->headers->set('referer', 'https://evil.example.com/attack');

        $response = $this->controller->changeLocale($request, 'en');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/', $response->getTargetUrl());
    }

    public function testMissingRefererFallsBackToHome(): void
    {
        $request = $this->createRequestWithSession();

        $response = $this->controller->changeLocale($request, 'en');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/', $response->getTargetUrl());
    }

    private function createRequestWithSession(): Request
    {
        $request = Request::create('/change-locale/en');
        $session = new Session(new MockArraySessionStorage());
        $request->setSession($session);

        return $request;
    }
}
