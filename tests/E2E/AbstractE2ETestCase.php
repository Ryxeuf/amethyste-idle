<?php

namespace App\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;
use Symfony\Component\Panther\PantherTestCase;

abstract class AbstractE2ETestCase extends PantherTestCase
{
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
        static::$pantherClient->waitFor('#inputEmail');

        static::$pantherClient->findElement(WebDriverBy::id('inputEmail'))->sendKeys($email);
        static::$pantherClient->findElement(WebDriverBy::id('inputPassword'))->sendKeys($password);
        static::$pantherClient->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();
    }
}
