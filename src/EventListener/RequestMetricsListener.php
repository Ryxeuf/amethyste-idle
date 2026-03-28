<?php

namespace App\EventListener;

use App\Service\Monitoring\MetricsCollector;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RequestMetricsListener implements EventSubscriberInterface
{
    private const EXCLUDED_PATHS = ['/metrics', '/health', '/_profiler', '/_wdt'];

    public function __construct(
        private readonly MetricsCollector $metricsCollector,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $event->getRequest()->attributes->set('_metrics_start', microtime(true));
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $path = $request->getPathInfo();

        foreach (self::EXCLUDED_PATHS as $excluded) {
            if (str_starts_with($path, $excluded)) {
                return;
            }
        }

        $start = $request->attributes->get('_metrics_start');
        if (!\is_float($start)) {
            return;
        }

        $duration = microtime(true) - $start;
        $method = $request->getMethod();
        $statusCode = $event->getResponse()->getStatusCode();
        $statusGroup = ((int) ($statusCode / 100)) . 'xx';

        $this->metricsCollector->incrementCounter(
            'http_requests_total',
            1.0,
            "method=\"{$method}\",status=\"{$statusGroup}\""
        );

        $this->metricsCollector->observeHistogram(
            'http_request_duration_seconds',
            $duration,
            "method=\"{$method}\""
        );
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $this->metricsCollector->incrementCounter('http_errors_total');
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [['onKernelRequest', 1024]],
            KernelEvents::RESPONSE => [['onKernelResponse', -1024]],
            KernelEvents::EXCEPTION => [['onKernelException', 0]],
        ];
    }
}
