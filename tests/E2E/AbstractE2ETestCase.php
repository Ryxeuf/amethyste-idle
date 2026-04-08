<?php

namespace App\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;
use Facebook\WebDriver\WebDriverExpectedCondition;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Panther\PantherTestCase;

#[Group('e2e')]
abstract class AbstractE2ETestCase extends PantherTestCase
{
    /** Default timeout in seconds for wait operations. */
    protected const WAIT_TIMEOUT = 10;

    protected function setUp(): void
    {
        parent::setUp();

        if (null === static::$pantherClient) {
            static::$pantherClient = static::createPantherClient([
                'browser' => static::CHROME,
            ]);
        }
    }

    protected function login(string $email = 'remy@amethyste.game', string $password = 'test'): void
    {
        static::$pantherClient->request('GET', '/login');
        $this->waitForSelector('#inputEmail');

        $emailField = static::$pantherClient->findElement(WebDriverBy::id('inputEmail'));
        $emailField->clear();
        $emailField->sendKeys($email);

        $passwordField = static::$pantherClient->findElement(WebDriverBy::id('inputPassword'));
        $passwordField->clear();
        $passwordField->sendKeys($password);

        static::$pantherClient->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

        // Wait for login redirect to complete
        $this->waitForUrlNotContaining('/login');
    }

    /**
     * Wait for PixiJS canvas to be rendered inside .map-canvas-container.
     * The map_pixi Stimulus controller appends a <canvas> element once initialized.
     */
    protected function waitForPixi(int $timeout = self::WAIT_TIMEOUT): void
    {
        static::$pantherClient->wait($timeout)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector('.map-canvas-container canvas')
            )
        );
    }

    /**
     * Wait for Turbo Drive navigation to settle.
     * Checks that no turbo-frame is in a loading state and the document is ready.
     */
    protected function waitForTurbo(int $timeout = self::WAIT_TIMEOUT): void
    {
        static::$pantherClient->wait($timeout)->until(function () {
            return static::$pantherClient->executeScript(
                "return document.readyState === 'complete'
                    && document.querySelectorAll('turbo-frame[busy]').length === 0
                    && !document.documentElement.hasAttribute('aria-busy');"
            );
        });
    }

    /**
     * Wait for a CSS selector to be present in the DOM.
     */
    protected function waitForSelector(string $selector, int $timeout = self::WAIT_TIMEOUT): void
    {
        static::$pantherClient->wait($timeout)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector($selector)
            )
        );
    }

    /**
     * Wait for the current URL to contain a given substring.
     */
    protected function waitForUrlContaining(string $substring, int $timeout = self::WAIT_TIMEOUT): void
    {
        static::$pantherClient->wait($timeout)->until(
            WebDriverExpectedCondition::urlContains($substring)
        );
    }

    /**
     * Wait for the current URL to NOT contain a given substring.
     */
    protected function waitForUrlNotContaining(string $substring, int $timeout = self::WAIT_TIMEOUT): void
    {
        static::$pantherClient->wait($timeout)->until(function () use ($substring) {
            return !str_contains(static::$pantherClient->getCurrentURL(), $substring);
        });
    }

    /**
     * Make an API call using PHP HTTP with the browser's session cookies.
     * Avoids WebDriver executeScript issues with async/sync JavaScript.
     */
    protected function apiFetch(string $url, string $method = 'POST', ?array $body = null): mixed
    {
        // Extract session cookies from the browser
        $cookies = static::$pantherClient->executeScript('return document.cookie;');

        // Resolve the base URL from the current page
        $currentUrl = static::$pantherClient->getCurrentURL();
        preg_match('#^(https?://[^/]+)#', $currentUrl, $m);
        $origin = $m[1] ?? 'http://127.0.0.1:9080';

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Cookie: ' . $cookies,
        ];

        $ch = curl_init($origin . $url);
        curl_setopt_array($ch, [
            \CURLOPT_CUSTOMREQUEST => $method,
            \CURLOPT_HTTPHEADER => $headers,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_TIMEOUT => 15,
        ]);
        if (null !== $body) {
            curl_setopt($ch, \CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, \CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (false === $response) {
            return ['error' => 'curl failed'];
        }
        if ($httpCode >= 400) {
            $decoded = json_decode($response, true);

            return $decoded ?? ['error' => 'HTTP ' . $httpCode];
        }

        return json_decode($response, true) ?? ['error' => 'Invalid JSON'];
    }

    /**
     * Wait for a Stimulus controller to be connected on the page.
     */
    protected function waitForStimulus(string $controllerName, int $timeout = self::WAIT_TIMEOUT): void
    {
        static::$pantherClient->wait($timeout)->until(
            WebDriverExpectedCondition::presenceOfElementLocated(
                WebDriverBy::cssSelector(sprintf('[data-controller*="%s"]', $controllerName))
            )
        );
    }

    /**
     * Check whether a CSS selector exists in the page without throwing.
     */
    protected function selectorExists(string $selector): bool
    {
        return (bool) static::$pantherClient->executeScript(sprintf(
            'return document.querySelector(%s) !== null;',
            json_encode($selector)
        ));
    }

    /**
     * Take a screenshot on test failure for CI debugging.
     */
    protected function takeScreenshot(string $name): void
    {
        $dir = __DIR__ . '/../../var/error-screenshots';
        if (!is_dir($dir)) {
            mkdir($dir, 0o777, true);
        }

        static::$pantherClient->takeScreenshot(sprintf('%s/%s-%s.png', $dir, $name, date('Y-m-d_H-i-s')));
    }

    protected function onNotSuccessfulTest(\Throwable $t): never
    {
        try {
            $testName = str_replace(['\\', '::'], ['-', '-'], static::class . '-' . $this->name());
            $this->takeScreenshot('failure-' . $testName);
        } catch (\Throwable) {
            // Ignorer les erreurs de screenshot pour ne pas masquer l'erreur originale
        }

        throw $t;
    }
}
