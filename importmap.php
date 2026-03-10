<?php

/**
 * Returns the importmap for this application.
 *
 * - "path" is a path inside the asset mapper system. Use the
 *     "debug:asset-map" command to see the full list of paths.
 *
 * - "entrypoint" (JavaScript only) set to true for any module that will
 *     be used as an "entrypoint" (and passed to the importmap() Twig function).
 *
 * The "importmap:require" command can be used to add new entries to this file.
 */
return [
    'app' => [
        'path' => './assets/app.js',
        'entrypoint' => true,
    ],
    '@hotwired/stimulus' => [
        'version' => '3.2.2',
    ],
    '@symfony/stimulus-bundle' => [
        'path' => './vendor/symfony/stimulus-bundle/assets/dist/loader.js',
    ],
    '@symfony/ux-live-component' => [
        'path' => './vendor/symfony/ux-live-component/assets/dist/live_controller.js',
    ],
    '@hotwired/turbo' => [
        'version' => '7.3.0',
    ],
    'pixi.js' => [
        'version' => '8.17.0',
    ],
    'eventemitter3' => [
        'version' => '5.0.4',
    ],
    '@pixi/colord' => [
        'version' => '2.9.6',
    ],
    '@pixi/colord/plugins/names' => [
        'version' => '2.9.6',
    ],
    'ismobilejs' => [
        'version' => '1.1.1',
    ],
    'earcut' => [
        'version' => '3.0.2',
    ],
    'parse-svg-path' => [
        'version' => '0.1.2',
    ],
    '@xmldom/xmldom' => [
        'version' => '0.8.11',
    ],
    'tiny-lru' => [
        'version' => '11.4.7',
    ],
    'pixi.js/lib/environment-browser/browserAll.mjs' => [
        'version' => '8.17.0',
    ],
    'pixi.js/lib/environment-webworker/webworkerAll.mjs' => [
        'version' => '8.17.0',
    ],
    'pixi.js/lib/rendering/renderers/gpu/WebGPURenderer.mjs' => [
        'version' => '8.17.0',
    ],
    'pixi.js/lib/rendering/renderers/gl/WebGLRenderer.mjs' => [
        'version' => '8.17.0',
    ],
    'pixi.js/lib/rendering/renderers/canvas/CanvasRenderer.mjs' => [
        'version' => '8.17.0',
    ],
];
