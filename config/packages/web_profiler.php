<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container): void {
    if (!class_exists(\Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class)) {
        return;
    }

    if ('dev' === $container->env()) {
        $container->extension('web_profiler', [
            'toolbar' => true,
            'intercept_redirects' => false,
        ]);
        $container->extension('framework', [
            'profiler' => [
                'only_exceptions' => false,
                'collect_serializer_data' => true,
            ],
        ]);
    }

    if ('test' === $container->env()) {
        $container->extension('web_profiler', [
            'toolbar' => false,
            'intercept_redirects' => false,
        ]);
        $container->extension('framework', [
            'profiler' => ['collect' => false],
        ]);
    }
};
