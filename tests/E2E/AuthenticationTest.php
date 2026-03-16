<?php

namespace App\Tests\E2E;

use Facebook\WebDriver\WebDriverBy;

class AuthenticationTest extends AbstractE2ETestCase
{
    public function testLoginPageIsAccessible(): void
    {
        static::$pantherClient->request('GET', '/login');

        $this->assertSelectorExists('#inputEmail');
        $this->assertSelectorExists('#inputPassword');
        $this->assertSelectorExists('button[type="submit"]');
    }

    public function testLoginWithValidCredentials(): void
    {
        $this->login('remy@amethyste.game', 'test');

        // Après connexion, on est redirigé vers la page d'accueil ou le jeu
        $this->assertStringNotContainsString('/login', static::$pantherClient->getCurrentURL());
    }

    public function testLoginWithInvalidCredentials(): void
    {
        static::$pantherClient->request('GET', '/login');

        static::$pantherClient->findElement(WebDriverBy::id('inputEmail'))->sendKeys('invalid@test.fr');
        static::$pantherClient->findElement(WebDriverBy::id('inputPassword'))->sendKeys('wrongpassword');
        static::$pantherClient->findElement(WebDriverBy::cssSelector('button[type="submit"]'))->click();

        // On reste sur la page de login avec une erreur
        $this->assertStringContainsString('/login', static::$pantherClient->getCurrentURL());
    }

    public function testLogout(): void
    {
        $this->login();

        static::$pantherClient->request('GET', '/logout');

        // Après déconnexion, on est redirigé
        $this->assertStringNotContainsString('/game', static::$pantherClient->getCurrentURL());
    }
}
