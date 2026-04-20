<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LocaleController extends AbstractController
{
    /**
     * @param list<string> $enabledLocales
     */
    public function __construct(
        #[Autowire('%kernel.enabled_locales%')]
        private readonly array $enabledLocales,
        #[Autowire('%kernel.default_locale%')]
        private readonly string $defaultLocale = 'fr',
    ) {
    }

    #[Route(
        '/change-locale/{locale}',
        name: 'app_change_locale',
        requirements: ['locale' => '[a-zA-Z_]{2,10}'],
        methods: ['GET', 'POST']
    )]
    public function changeLocale(Request $request, string $locale): Response
    {
        if (!in_array($locale, $this->enabledLocales, true)) {
            $locale = $this->defaultLocale;
        }

        $request->getSession()->set('_locale', $locale);

        $referer = $request->headers->get('referer');
        if (is_string($referer) && $this->isSafeReferer($referer, $request)) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('app_home');
    }

    private function isSafeReferer(string $referer, Request $request): bool
    {
        $parsed = parse_url($referer);
        if (!is_array($parsed) || !isset($parsed['host'])) {
            return false;
        }

        return $parsed['host'] === $request->getHost();
    }
}
