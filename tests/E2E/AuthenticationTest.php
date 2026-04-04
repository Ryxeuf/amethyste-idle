<?php

namespace App\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;

class AuthenticationTest extends AbstractE2ETestCase
{
    public function testLoginPageIsAccessible(): void
    {
        static::$pantherClient->request('GET', '/login');
        $this->waitForSelector('#inputEmail');

        $this->assertSelectorExists('#inputEmail');
        $this->assertSelectorExists('#inputPassword');
        $this->assertSelectorExists('button[type="submit"]');
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->login('remy@amethyste.game', 'test');

        // login() already waits for redirect — verify we left /login
        $this->assertStringNotContainsString('/login', static::$pantherClient->getCurrentURL());
    }

    public function testLoginWithInvalidCredentials(): void
    {
        static::$pantherClient->request('GET', '/login');
        $this->waitForSelector('#inputEmail');

        $emailField = static::$pantherClient->findElement(WebDriverBy::id('inputEmail'));
        $emailField->clear();
        $emailField->sendKeys('invalid@test.fr');

        $passwordField = static::$pantherClient->findElement(WebDriverBy::id('inputPassword'));
        $passwordField->clear();
        $passwordField->sendKeys('wrongpassword');
        static::$pantherClient->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

        $this->waitForSelector('body');

        // On reste sur la page de login avec une erreur
        $this->assertStringContainsString('/login', static::$pantherClient->getCurrentURL());
    }

    public function testLogout(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/logout');
        $this->waitForUrlNotContaining('/game');

        $this->assertStringNotContainsString('/game', static::$pantherClient->getCurrentURL());
    }
}
