<?php

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    if (!class_exists(\Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class)) {
        return;
    }

    if ('dev' !== $routes->env()) {
        return;
    }

    $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.xml')->prefix('/_wdt');
    $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.xml')->prefix('/_profiler');
};
