<?php

/*
 * Router script for PHP's built-in web server.
 * Used by Panther E2E tests to route clean URLs to index.php.
 */

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = $_SERVER['DOCUMENT_ROOT'] . $path;

// Serve existing files directly (CSS, JS, images, etc.)
if (is_file($file)) {
    return false;
}

// Route everything else through Symfony's front controller
require $_SERVER['DOCUMENT_ROOT'] . '/index.php';
